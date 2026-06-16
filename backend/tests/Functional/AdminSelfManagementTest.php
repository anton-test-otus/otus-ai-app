<?php

namespace App\Tests\Functional;

use App\Entity\User;

class AdminSelfManagementTest extends ApiTestCase
{
    public function testSoleAdminCannotDisableDeleteOrDemoteSelf(): void
    {
        $adminOnly = $this->userFactory->createUser('admin-only@example.com', ['ROLE_ADMIN']);
        $token = $this->login($adminOnly);
        $adminId = $adminOnly->getId()->toRfc4122();

        $this->jsonRequest('PATCH', '/api/admin/users/' . $adminId . '/disable', null, $token);
        self::assertResponseStatusCodeSame(400);

        $this->jsonRequest('DELETE', '/api/admin/users/' . $adminId, null, $token);
        self::assertResponseStatusCodeSame(400);

        $this->jsonRequest('PATCH', '/api/admin/users/' . $adminId . '/demote', null, $token);
        self::assertResponseStatusCodeSame(409);
    }

    public function testTwoAdminsDemoteFlow(): void
    {
        $adminA = $this->userFactory->createUser('admin-a@example.com', ['ROLE_ADMIN']);
        $adminB = $this->userFactory->createUser('admin-b@example.com', ['ROLE_ADMIN']);
        $tokenA = $this->login($adminA);

        $adminBId = $adminB->getId()->toRfc4122();
        $adminAId = $adminA->getId()->toRfc4122();

        $this->jsonRequest('PATCH', '/api/admin/users/' . $adminBId . '/demote', null, $tokenA);
        self::assertResponseIsSuccessful();

        $adminBDetails = $this->fetchAdminUser($adminBId, $tokenA);
        self::assertNotContains('ROLE_ADMIN', $adminBDetails['roles']);

        $this->jsonRequest('PATCH', '/api/admin/users/' . $adminAId . '/demote', null, $tokenA);
        self::assertResponseStatusCodeSame(409);
    }

    public function testAdminCanDisableDeleteAndPromoteRegularUser(): void
    {
        $adminA = $this->userFactory->createUser('admin-a@example.com', ['ROLE_ADMIN']);
        $userRegular = $this->userFactory->createUser('user-regular@example.com');
        $tokenA = $this->login($adminA);

        $regularId = $userRegular->getId()->toRfc4122();

        $this->jsonRequest('PATCH', '/api/admin/users/' . $regularId . '/disable', null, $tokenA);
        self::assertResponseIsSuccessful();

        $this->entityManager->clear();
        /** @var User $disabledUser */
        $disabledUser = $this->entityManager->getRepository(User::class)->find($regularId);
        self::assertFalse($disabledUser->isActive());

        $activeRegular = $this->userFactory->createUser('user-regular-2@example.com');
        $activeRegularId = $activeRegular->getId()->toRfc4122();

        $this->jsonRequest('DELETE', '/api/admin/users/' . $activeRegularId, null, $tokenA);
        self::assertResponseIsSuccessful();

        $this->entityManager->clear();
        self::assertNull($this->entityManager->getRepository(User::class)->find($activeRegularId));

        $promoteTarget = $this->userFactory->createUser('user-promote@example.com');
        $promoteTargetId = $promoteTarget->getId()->toRfc4122();

        $this->jsonRequest('PATCH', '/api/admin/users/' . $promoteTargetId . '/promote', null, $tokenA);
        self::assertResponseIsSuccessful();

        $promotedUser = $this->fetchAdminUser($promoteTargetId, $tokenA);
        self::assertContains('ROLE_ADMIN', $promotedUser['roles']);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchAdminUser(string $userId, string $token): array
    {
        $this->jsonRequest('GET', '/api/admin/users/' . $userId, null, $token);
        self::assertResponseIsSuccessful();

        return $this->responseJson();
    }
}
