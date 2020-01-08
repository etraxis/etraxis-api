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

namespace eTraxis\Subscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles any unhandled exception.
 */
class UnhandledException implements EventSubscriberInterface
{
    private $logger;
    private $translator;
    private $normalizer;

    /**
     * @codeCoverageIgnore Dependency Injection constructor.
     *
     * @param LoggerInterface     $logger
     * @param TranslatorInterface $translator
     * @param NormalizerInterface $normalizer
     */
    public function __construct(LoggerInterface $logger, TranslatorInterface $translator, NormalizerInterface $normalizer)
    {
        $this->logger     = $logger;
        $this->translator = $translator;
        $this->normalizer = $normalizer;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    /**
     * In case of AJAX: logs the exception and converts it into JSON response with HTTP error.
     *
     * @param ExceptionEvent $event
     *
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function onException(ExceptionEvent $event): void
    {
        $request   = $event->getRequest();
        $throwable = $event->getThrowable();

        if ($request->isXmlHttpRequest() || $request->getContentType() === 'json') {

            if ($throwable instanceof HandlerFailedException) {
                $throwable = $throwable->getPrevious();
            }

            if ($throwable instanceof ValidationFailedException) {
                $message    = $throwable->getMessage() ?: $this->getHttpErrorMessage(JsonResponse::HTTP_BAD_REQUEST);
                $violations = $this->normalizer->normalize($throwable->getViolations());
                $this->logger->error('Validation exception', ['message' => $message, 'violations' => $violations]);
                $response = new JsonResponse($violations, JsonResponse::HTTP_BAD_REQUEST);
                $event->setResponse($response);
            }
            elseif ($throwable instanceof HttpException) {
                $message = $throwable->getMessage() ?: $this->getHttpErrorMessage($throwable->getStatusCode());
                $this->logger->error('HTTP exception', ['code' => $throwable->getStatusCode(), 'message' => $message]);
                $response = new JsonResponse($message, $throwable->getStatusCode());
                $event->setResponse($response);
            }
            else {
                $message = $throwable->getMessage() ?: $this->getHttpErrorMessage(JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
                $this->logger->critical('Exception', ['error' => $message]);
                $response = new JsonResponse($message, JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
                $event->setResponse($response);
            }
        }
    }

    /**
     * Returns user-friendly error message for specified HTTP status code.
     *
     * @param int $statusCode
     *
     * @return string
     */
    private function getHttpErrorMessage(int $statusCode): string
    {
        switch ($statusCode) {

            case JsonResponse::HTTP_UNAUTHORIZED:
                return $this->translator->trans('Authentication required.');

            case JsonResponse::HTTP_FORBIDDEN:
                return $this->translator->trans('http_error.403');

            case JsonResponse::HTTP_NOT_FOUND:
                return $this->translator->trans('http_error.404');

            default:
                return $this->translator->trans('http_error');
        }
    }
}
