<?php

namespace App\Controller\Admin;

use App\Entity\AdminUser;
use App\Repository\AdminUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/admin/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    private const MIN_PASSWORD_LENGTH = 12;

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'admin_forgot_password_request', methods: ['GET', 'POST'])]
    public function request(
        Request $request,
        AdminUserRepository $userRepository,
        MailerInterface $mailer,
        LoggerInterface $logger,
        #[Autowire('%env(MAILER_FROM)%')] string $mailerFrom,
    ): Response {
        if (!$request->isMethod('POST')) {
            return $this->render('admin/reset_password/request.html.twig');
        }

        if (!$this->isCsrfTokenValid('reset_password_request', $request->request->getString('_csrf_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user = $userRepository->findOneBy(['email' => trim($request->request->getString('email'))]);

        // On redirige toujours vers la même page pour ne pas révéler si l'email existe
        if ($user) {
            try {
                $resetToken = $this->resetPasswordHelper->generateResetToken($user);

                $mailer->send((new TemplatedEmail())
                    ->from(new Address($mailerFrom, 'Portfolio Admin'))
                    ->to((string) $user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->htmlTemplate('admin/reset_password/email.html.twig')
                    ->context(['resetToken' => $resetToken]));

                $this->setTokenObjectInSession($resetToken);
            } catch (ResetPasswordExceptionInterface $e) {
                // Demande trop rapprochée de la précédente : on reste silencieux côté utilisateur
                $logger->info('Demande de réinitialisation ignorée : {reason}', ['reason' => $e->getReason()]);
            } catch (TransportExceptionInterface $e) {
                $logger->error('Échec d\'envoi de l\'email de réinitialisation : {message}', ['message' => $e->getMessage()]);
            }
        }

        return $this->redirectToRoute('admin_check_email');
    }

    #[Route('/check-email', name: 'admin_check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        // Génère un faux jeton si aucun email n'a été envoyé, pour ne pas révéler l'existence du compte
        $resetToken = $this->getTokenObjectFromSession() ?? $this->resetPasswordHelper->generateFakeResetToken();

        return $this->render('admin/reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    #[Route('/reset/{token}', name: 'admin_reset_password', methods: ['GET', 'POST'])]
    public function reset(Request $request, UserPasswordHasherInterface $hasher, ?string $token = null): Response
    {
        if ($token) {
            // Le jeton est retiré de l'URL et stocké en session pour éviter qu'il fuite (historique, Referer...)
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('admin_reset_password');
        }

        $token = $this->getTokenFromSession();
        if (null === $token) {
            throw $this->createNotFoundException('Aucun jeton de réinitialisation trouvé.');
        }

        try {
            /** @var AdminUser $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', 'Ce lien de réinitialisation est invalide ou a expiré. Merci de refaire une demande.');

            return $this->redirectToRoute('admin_forgot_password_request');
        }

        $error = null;
        if ($request->isMethod('POST')) {
            $password = $request->request->getString('password');

            if (!$this->isCsrfTokenValid('reset_password', $request->request->getString('_csrf_token'))) {
                $error = 'Session expirée, merci de réessayer.';
            } elseif (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
                $error = sprintf('Le mot de passe doit contenir au moins %d caractères.', self::MIN_PASSWORD_LENGTH);
            } elseif ($password !== $request->request->getString('password_confirm')) {
                $error = 'Les deux mots de passe ne correspondent pas.';
            } else {
                $this->resetPasswordHelper->removeResetRequest($token);
                $user->setPassword($hasher->hashPassword($user, $password));
                $this->em->flush();
                $this->cleanSessionAfterReset();

                $this->addFlash('success', 'Mot de passe modifié. Vous pouvez vous connecter.');

                return $this->redirectToRoute('admin_login');
            }
        }

        return $this->render('admin/reset_password/reset.html.twig', [
            'error' => $error,
            'min_length' => self::MIN_PASSWORD_LENGTH,
        ]);
    }
}
