<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Note;
use App\Entity\NoteVersion;
use App\Repository\NoteRepository;
use App\Service\NoteLinkSyncService;
use App\Service\NoteVersionService;
use Symfony\Bundle\SecurityBundle\Security;
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

        $user = $this->security->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException('User not authenticated');
        }

        $note = $data->getNote();
        
        if (!$note) {
            throw new NotFoundHttpException('Не найдена');
        }

        if ($note->getUser() !== $user) {
            throw new AccessDeniedHttpException('You do not have access to this note');
        }

        $restoreMode = $context['request_data']['mode'] ?? 'overwrite';

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
}
