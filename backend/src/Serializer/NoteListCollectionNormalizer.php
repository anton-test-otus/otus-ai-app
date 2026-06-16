<?php

namespace App\Serializer;

use ApiPlatform\State\Pagination\PaginatorInterface;
use App\Entity\Note;
use App\Entity\User;
use App\Service\NotePreviewService;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class NoteListCollectionNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'note_list_collection_normalizer_called';

    public function __construct(
        private NotePreviewService $notePreviewService,
    ) {
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $notes = $this->extractNotes($object);
        if ($notes !== []) {
            $user = $this->resolveUser($notes);
            if ($user instanceof User) {
                $context[NotePreviewService::CONTEXT_WIKI_TITLES_BY_ID] = $this->notePreviewService->prefetchWikiTitlesForNotes(
                    $notes,
                    $user,
                );
            }
        }

        $context[self::ALREADY_CALLED] = true;

        return $this->normalizer->normalize($object, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        if (!\in_array('note:list', $context['groups'] ?? [], true)) {
            return false;
        }

        if ($data instanceof PaginatorInterface) {
            return $this->paginatorContainsNotes($data);
        }

        return \is_array($data) && $this->isNoteList($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            PaginatorInterface::class => false,
            'array' => false,
        ];
    }

    /**
     * @return Note[]
     */
    private function extractNotes(mixed $object): array
    {
        if ($object instanceof PaginatorInterface) {
            $notes = [];
            foreach ($object as $item) {
                if ($item instanceof Note) {
                    $notes[] = $item;
                }
            }

            return $notes;
        }

        if (\is_array($object)) {
            return array_values(array_filter($object, static fn ($item): bool => $item instanceof Note));
        }

        return [];
    }

    /**
     * @param Note[] $notes
     */
    private function resolveUser(array $notes): ?User
    {
        return $notes[0]->getUser();
    }

    /**
     * @param mixed[] $data
     */
    private function isNoteList(array $data): bool
    {
        if ($data === []) {
            return true;
        }

        if (!array_is_list($data)) {
            return false;
        }

        foreach ($data as $item) {
            if (!$item instanceof Note) {
                return false;
            }
        }

        return true;
    }

    private function paginatorContainsNotes(PaginatorInterface $paginator): bool
    {
        foreach ($paginator as $item) {
            return $item instanceof Note;
        }

        return true;
    }
}
