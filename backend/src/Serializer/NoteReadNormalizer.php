<?php

namespace App\Serializer;

use App\Entity\Note;
use App\Repository\NoteLinkRepository;
use App\Repository\NoteVersionRepository;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class NoteReadNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'note_read_normalizer_called';

    public function __construct(
        private NoteLinkRepository $noteLinkRepository,
        private NoteVersionRepository $versionRepository,
    ) {
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var array<string, mixed> $data */
        $data = $this->normalizer->normalize($object, $format, $context);

        if ($object instanceof Note) {
            $data['linkStats'] = $this->noteLinkRepository->countLinkStats($object);
            $data['versionCount'] = $this->versionRepository->countByNote($object);
        }

        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Note && \in_array('note:read', $context['groups'] ?? [], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Note::class => false,
        ];
    }
}
