<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <http://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\Application\Command\Users\Handler;

use eTraxis\Application\Command\Users\ForgetPasswordCommand;
use eTraxis\Repository\Contracts\UserRepositoryInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Command handler.
 */
class ForgetPasswordHandler
{
    private TranslatorInterface     $translator;
    private MailerInterface         $mailer;
    private UserRepositoryInterface $repository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param TranslatorInterface     $translator
     * @param MailerInterface         $mailer
     * @param UserRepositoryInterface $repository
     */
    public function __construct(
        TranslatorInterface     $translator,
        MailerInterface         $mailer,
        UserRepositoryInterface $repository
    )
    {
        $this->translator = $translator;
        $this->mailer     = $mailer;
        $this->repository = $repository;
    }

    /**
     * Command handler.
     *
     * @param ForgetPasswordCommand $command
     *
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     *
     * @return null|string Generated reset token (NULL if user not found).
     */
    public function __invoke(ForgetPasswordCommand $command): ?string
    {
        /** @var null|\eTraxis\Entity\User $user */
        $user = $this->repository->loadUserByUsername($command->email);

        if (!$user || $user->isAccountExternal()) {
            return null;
        }

        // Token expires in 2 hours.
        $token = $user->generateResetToken(new \DateInterval('PT2H'));
        $this->repository->persist($user);

        $message = new TemplatedEmail();

        $address = new Address($user->email, $user->fullname);
        $subject = $this->translator->trans('email.forgot_password.subject', [], null, $user->locale);

        $message
            ->to($address)
            ->subject($subject)
            ->htmlTemplate('security/forgot/email.html.twig')
            ->context([
                'locale' => $user->locale,
                'token'  => $token,
            ]);

        $this->mailer->send($message);

        return $token;
    }
}
