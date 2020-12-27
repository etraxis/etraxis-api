<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2018 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace eTraxis\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use eTraxis\Application\Dictionary\EventType;
use eTraxis\Entity\Comment;
use eTraxis\Entity\Event;

/**
 * Test fixtures for 'Comment' entity.
 */
class CommentFixtures extends Fixture implements DependentFixtureInterface, FixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            IssueFixtures::class,
            EventFixtures::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $data = [

            'task:%s:1' => [
                EventType::PUBLIC_COMMENT => [
                    'Saepe enim voluptatem minima eum minus voluptas. Impedit est quis rem accusantium maiores odit. Soluta quos corporis atque corporis optio nemo. Incidunt perspiciatis voluptatem sed molestias. Eius nemo molestiae sit vitae. Quibusdam doloribus facere a. Quia sapiente rem provident quos quos id consequatur. Laudantium repellendus omnis voluptates numquam. Assumenda ut et voluptatum nisi omnis.',
                ],
            ],

            'task:%s:2' => [
                EventType::PUBLIC_COMMENT  => [
                    'Assumenda dolor tempora nisi tempora tempore. Error qui aperiam eaque aut culpa. Rerum error sit ut et voluptatem odit. Quisquam nulla incidunt repudiandae. Qui maxime laboriosam accusantium dolores aut perspiciatis. Numquam dolores ipsum et sapiente incidunt consectetur aut. Quo facere et velit. Doloribus velit a nam odit architecto illo ex. Voluptas odio et recusandae. Quam quibusdam et mollitia vitae eveniet nulla. Placeat sit corporis eos accusamus est voluptatibus quos repellendus. Et ipsam dolores reiciendis dolorem eum vel.'
                    . "\n" . 'Architecto perferendis velit et ut. Amet assumenda veritatis praesentium ea iure ut ea aliquid. Molestias laboriosam et in. Tempora assumenda dolorem sunt. Dolores sit ratione debitis ex quae nesciunt. Odit beatae hic temporibus doloribus dolorem unde. Vel aperiam et cupiditate magni. Minima ut culpa distinctio architecto natus. Ea soluta voluptatum quas. Accusamus assumenda maxime optio. Natus voluptates odit sit.',
                    'Natus excepturi est eaque nostrum non. Deleniti vitae perspiciatis distinctio voluptatum. Doloribus quod et ipsum. Qui officiis voluptatem itaque vel. Aut a sapiente ut dolorem doloremque corrupti et. Vel cumque saepe pariatur minus quaerat voluptas fuga. Rerum alias maxime voluptate dolor numquam at. Velit et dicta dolore nihil. Autem fugit nam est vel. Adipisci quis tempore soluta amet. Ducimus natus vitae maxime doloremque quaerat.',
                ],
                EventType::PRIVATE_COMMENT => [
                    'Ut ipsum explicabo iste sequi dignissimos. Et voluptatibus dolorum voluptas porro odio. Maiores debitis soluta deserunt tenetur totam consequatur nisi iusto. Occaecati itaque quae omnis sequi in dolor dolor. Modi eum sunt quidem impedit. Quisquam minus at occaecati quaerat sunt fugit. Sunt modi in enim repellat velit blanditiis iure. Omnis similique voluptatem voluptas qui esse ducimus ut. Optio id repellendus odio qui fugit qui. Provident reprehenderit in odio repudiandae corporis est.',
                ],
            ],
        ];

        foreach (['a', 'b', 'c'] as $pref) {

            foreach ($data as $iref => $events) {

                /** @var \eTraxis\Entity\Issue $issue */
                $issue = $this->getReference(sprintf($iref, $pref));
                $manager->refresh($issue);

                foreach ($events as $event_type => $comments) {

                    /** @var Event[] $events */
                    $events = $manager->getRepository(Event::class)->findBy([
                        'type'  => $event_type,
                        'issue' => $issue,
                    ], [
                        'createdAt' => 'ASC',
                    ]);

                    foreach ($comments as $index => $body) {

                        $comment = new Comment($events[$index]);

                        $comment->body      = $body;
                        $comment->isPrivate = $event_type === EventType::PRIVATE_COMMENT;

                        $manager->persist($comment);
                    }
                }
            }
        }

        $manager->flush();
    }
}
