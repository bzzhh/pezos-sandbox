<?php

declare(strict_types=1);

namespace PezosSandbox\Infrastructure\TalisOrm;

use PezosSandbox\Application\Members\Member;
use PezosSandbox\Application\Members\MemberForAdministrator;
use PezosSandbox\Application\Members\Members;
use PezosSandbox\Domain\Model\Member\CouldNotFindMember;
use PezosSandbox\Domain\Model\Member\PubKey;
use PezosSandbox\Infrastructure\Doctrine\Connection;
use PezosSandbox\Infrastructure\Doctrine\NoResult;
use PezosSandbox\Infrastructure\Mapping;

final class MembersUsingDoctrineDbal implements Members
{
    use Mapping;

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getOneByPubKey(PubKey $pubKey): Member
    {
        try {
            $data = $this->connection->selectOne(
                $this->connection
                    ->createQueryBuilder()
                    ->select('*')
                    ->from('members')
                    ->andWhere('pub_key = :pub_key')
                    ->setParameter('pub_key', $pubKey->asString())
            );

            return $this->createMember($data);
        } catch (NoResult $exception) {
            throw CouldNotFindMember::withPubKey($pubKey);
        }
    }

    public function listMembers(): array
    {
        $records = $this->connection->selectAll(
            $this->connection
                ->createQueryBuilder()
                ->select('*')
                ->from('members')
                ->orderBy('wasGrantedAccess', 'desc')
        );

        return array_map(
            fn (
                array $record
            ): MemberForAdministrator => new MemberForAdministrator(
                self::asString($record, 'address'),
                self::asString($record, 'requestedAccessAt'),
                self::asBool($record, 'wasGrantedAccess')
            ),
            $records
        );
    }

    /**
     * @param array<string,mixed> $data
     */
    private function createMember($data): Member
    {
        return new Member(
            self::asString($data, 'pub_key'),
            self::asString($data, 'address')
        );
    }
}
