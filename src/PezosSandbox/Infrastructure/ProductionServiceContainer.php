<?php

declare(strict_types=1);

namespace PezosSandbox\Infrastructure;

use Doctrine\DBAL\Connection as DbalConnection;
use PezosSandbox\Application\Clock;
use PezosSandbox\Application\Members\Members;
use PezosSandbox\Application\Tokens\Tokens;
use PezosSandbox\Domain\Model\Member\MemberRepository;
use PezosSandbox\Domain\Model\Token\TokenRepository;
use PezosSandbox\Infrastructure\Doctrine\Connection;
use PezosSandbox\Infrastructure\TalisOrm\EventDispatcherAdapter;
use PezosSandbox\Infrastructure\TalisOrm\MembersUsingDoctrineDbal;
use PezosSandbox\Infrastructure\TalisOrm\MemberTalisOrmRepository;
use PezosSandbox\Infrastructure\TalisOrm\TokensUsingDoctrineDbal;
use PezosSandbox\Infrastructure\TalisOrm\TokenTalisOrmRepository;
use TalisOrm\AggregateRepository;

class ProductionServiceContainer extends ServiceContainer
{
    private DbalConnection $dbalConnection;

    public function __construct(DbalConnection $connection)
    {
        $this->dbalConnection = $connection;
    }

    protected function clock(): Clock
    {
        return new SystemClock();
    }

    protected function memberRepository(): MemberRepository
    {
        return new MemberTalisOrmRepository(
            $this->talisOrmAggregateRepository(),
        );
    }

    protected function tokenRepository(): TokenRepository
    {
        return new TokenTalisOrmRepository(
            $this->talisOrmAggregateRepository(),
        );
    }

    protected function members(): Members
    {
        return new MembersUsingDoctrineDbal($this->connection());
    }

    protected function tokens(): Tokens
    {
        return new TokensUsingDoctrineDbal($this->connection());
    }

    private function talisOrmAggregateRepository(): AggregateRepository
    {
        return new AggregateRepository(
            $this->dbalConnection,
            new EventDispatcherAdapter($this->eventDispatcher()),
        );
    }

    private function connection(): Connection
    {
        return new Connection($this->dbalConnection);
    }
}
