<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Note;
use App\Entity\NoteVersion;
use App\Repository\NoteRepository;
use App\Service\NoteLinkSyncService;
use App\Service\NoteVersionService;
use App\Security\AuthenticatedUserAssert;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RestoreVersionProcessor implements ProcessorInterface
{
    public function __construct(
        private NoteVersionService $versionService,
        private NoteLinkSyncService $noteLinkSyncService,
        private NoteRepository $noteRepository,
        private Security $security
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof NoteVersion) {
            throw new BadRequestException('Invalid data type');
        }

        $user = AuthenticatedUserAssert::requirePersistedUser($this->security->getUser());

        $note = $data->getNote();
        
        if (!$note) {
            throw new NotFoundHttpException('Не найдена');
        }

        if ($note->getUser() !== $user) {
            throw new AccessDeniedHttpException('You do not have access to this note');
        }

        $restoreMode = $this->resolveRestoreMode($context);

        switch ($restoreMode) {
            case 'create_version':
                $this->versionService->restoreFromVersion($note, $data, createNewVersion: true);
                break;
            
            case 'overwrite':
                $this->versionService->restoreFromVersion($note, $data, createNewVersion: false);
                break;
            
            case 'copy':
                $note = $this->versionService->createNoteFromVersion($data, $note);
                break;
            
            default:
                throw new BadRequestException('Invalid restore mode. Use: create_version, overwrite, or copy');
        }

        $this->noteLinkSyncService->syncFromContent($note);

        return $note;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function resolveRestoreMode(array $context): string
    {
        $request = $context['request'] ?? null;
        if (!$request instanceof Request) {
            return 'overwrite';
        }

        try {
            $mode = $request->getPayload()->get('mode', 'overwrite');
        } catch (\JsonException) {
            return 'overwrite';
        }

        return is_string($mode) ? $mode : 'overwrite';
    }
}
