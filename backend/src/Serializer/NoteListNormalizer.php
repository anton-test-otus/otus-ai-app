<?php

namespace App\Serializer;

use App\Entity\Note;
use App\Service\NotePreviewService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class NoteListNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'note_list_normalizer_called';

    public function __construct(
        private NotePreviewService $notePreviewService,
    ) {
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var array<string, mixed> $data */
        $data = $this->normalizer->normalize($object, $format, $context);

        if ($object instanceof Note) {
            $data['contentPreview'] = $this->notePreviewService->buildPreview(
                $object->getContent(),
                $object->getUser(),
            );
        }

        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (isset($context[self::ALREADY_CALLED])) {
            return false;
        }

        return $data instanceof Note && \in_array('note:list', $context['groups'] ?? [], true);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Note::class => false,
        ];
    }
}
