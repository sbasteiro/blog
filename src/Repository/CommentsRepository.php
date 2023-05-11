<?php

namespace App\Repository;

use App\Entity\Comments;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Comments|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comments|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comments[]    findAll()
 * @method Comments[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comments::class);
    }

    public function searchComments($id_user){
        return $this->getEntityManager()
            ->createQuery('
                SELECT comment.id, post.title, post.id
                FROM App:Comments comment
                JOIN comment.posts post
                WHERE comment.user =:user_id
            ')
            ->setParameter('user_id',$id_user)
            ->setMaxResults(10)
            ->getResult();
    }

    public function searchPostComments($post_id){
        return $this->getEntityManager()
            ->createQuery('
                SELECT comment.comment, user.name
                FROM App:Comments comment
                JOIN comment.user user
                WHERE comment.posts =:post_id
            ')
            ->setParameter('post_id',$post_id);
    }
}