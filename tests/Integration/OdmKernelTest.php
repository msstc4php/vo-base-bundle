<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Bundle\Tests\Integration;

use Doctrine\Bundle\MongoDBBundle\DoctrineMongoDBBundle;
use Doctrine\ODM\MongoDB\Types\Type;
use MSSTC4PHP\ValueObject\Bundle\Tests\Integration\Kernel\IntegrationKernel;
use MSSTC4PHP\ValueObject\Doctrine\Odm\EmailAddressType;
use MSSTC4PHP\ValueObject\Identity\EmailAddress;
use Override;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\TestCase;

/**
 * The ODM half of the same proof. No MongoDB server is involved: what is under
 * test is that DoctrineMongoDBBundle accepts the prepended map and the ODM
 * registry holds this branch's classes afterwards.
 *
 * Covering only ORM end-to-end would have made the asymmetry a coverage gap
 * rather than a decision.
 */
#[CoversNothing]
#[RequiresMethod(DoctrineMongoDBBundle::class, 'build')]
final class OdmKernelTest extends TestCase
{
    private const array ODM_CONFIG = [
        'connections' => ['default' => ['server' => 'mongodb://localhost:27017']],
        'default_database' => 'vo_base_bundle_test',
        'document_managers' => ['default' => ['mappings' => []]],
    ];

    // A debug kernel registers ErrorHandler on boot; early Symfony 7.4 leaves it installed after shutdown.
    #[Override]
    protected function tearDown(): void
    {
        \restore_exception_handler();
    }

    public function testKernelBootsAndTheOdmRegistryKnowsTheTypes(): void
    {
        $kernel = new IntegrationKernel(
            [new DoctrineMongoDBBundle()],
            ['doctrine_mongodb' => self::ODM_CONFIG],
            'odm',
        );
        $kernel->boot();

        self::assertTrue(Type::hasType(EmailAddressType::NAME));
        self::assertInstanceOf(EmailAddressType::class, Type::getType(EmailAddressType::NAME));

        $kernel->shutdown();
    }

    public function testRegisteredTypeBelongsToTheOdmBranch(): void
    {
        $kernel = new IntegrationKernel(
            [new DoctrineMongoDBBundle()],
            ['doctrine_mongodb' => self::ODM_CONFIG],
            'odm-branch',
        );
        $kernel->boot();

        // Type names are identical across branches, so only the class tells them
        // apart -- this is what would catch the two maps being swapped.
        self::assertStringContainsString('\\Odm\\', Type::getType(EmailAddressType::NAME)::class);

        $kernel->shutdown();
    }

    public function testValueObjectSurvivesTheRegisteredType(): void
    {
        $kernel = new IntegrationKernel(
            [new DoctrineMongoDBBundle()],
            ['doctrine_mongodb' => self::ODM_CONFIG],
            'odm-roundtrip',
        );
        $kernel->boot();

        $type = Type::getType(EmailAddressType::NAME);
        $email = EmailAddress::from('user@example.com');
        $back = $type->convertToPHPValue($type->convertToDatabaseValue($email));

        self::assertInstanceOf(EmailAddress::class, $back);
        self::assertTrue($email->equals($back));

        $kernel->shutdown();
    }
}
