<?php

namespace App\Tests\Controller\Admin;

use App\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ResetPasswordTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        foreach (['reset_password_request', 'admin_user'] as $table) {
            $this->em->getConnection()->executeStatement("DELETE FROM $table");
        }

        $admin = (new AdminUser())->setEmail('admin@test.local');
        $admin->setPassword(static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($admin, 'ancien-mot-de-passe'));
        $this->em->persist($admin);
        $this->em->flush();
    }

    public function testFullResetFlow(): void
    {
        $crawler = $this->client->request('GET', '/admin/login');
        $this->client->click($crawler->selectLink('Mot de passe oublié ?')->link());
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Envoyer le lien', ['email' => 'admin@test.local']);
        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertEmailAddressContains($email, 'to', 'admin@test.local');

        self::assertResponseRedirects('/admin/reset-password/check-email');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Vérifiez vos emails');

        preg_match('#href="(http[^"]+/admin/reset-password/reset/[^"]+)"#', $email->getHtmlBody(), $matches);
        self::assertNotEmpty($matches, 'Le lien de réinitialisation doit figurer dans l\'email');

        $this->client->request('GET', $matches[1]);
        self::assertResponseRedirects('/admin/reset-password/reset');
        $this->client->followRedirect();

        // Mots de passe différents : refusé
        $this->client->submitForm('Enregistrer', ['password' => 'nouveau-mot-de-passe', 'password_confirm' => 'autre-chose-123']);
        self::assertSelectorTextContains('body', 'ne correspondent pas');

        $this->client->submitForm('Enregistrer', ['password' => 'nouveau-mot-de-passe', 'password_confirm' => 'nouveau-mot-de-passe']);
        self::assertResponseRedirects('/admin/login');
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Mot de passe modifié');

        // Le nouveau mot de passe permet de se connecter
        $this->client->submitForm('Se connecter', ['_username' => 'admin@test.local', '_password' => 'nouveau-mot-de-passe']);
        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('/admin/login', $this->client->getRequest()->getUri());

        // Le lien ne peut pas être réutilisé
        $this->client->request('GET', $matches[1]);
        $this->client->followRedirect();
        self::assertResponseRedirects('/admin/reset-password');
    }

    public function testUnknownEmailDoesNotRevealAnything(): void
    {
        $this->client->request('GET', '/admin/reset-password');
        $this->client->submitForm('Envoyer le lien', ['email' => 'inconnu@test.local']);

        self::assertEmailCount(0);
        self::assertResponseRedirects('/admin/reset-password/check-email');
    }
}
