<?php

namespace App\Serializer;

use App\Entity\Note;
use App\Repository\NoteLinkRepository;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class NoteReadNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const ALREADY_CALLED = 'note_read_normalizer_called';

    public function __construct(
        private NoteLinkRepository $noteLinkRepository,
    ) {
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context[self::ALREADY_CALLED] = true;

        /** @var array<string, mixed> $data */
        $data = $this->normalizer->normalize($object, $format, $context);

        if ($object instanceof Note) {
            $metadata = $this->noteLinkRepository->getNoteReadMetadata($object);
            $data['linkStats'] = $metadata['linkStats'];
            $data['versionCount'] = $metadata['versionCount'];
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
