<?php

namespace App\Controller;

use App\Entity\Comments;
use App\Entity\Posts;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    private $em;

    /**
     * @param $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/', name: 'app_dashboard')]
    public function index(PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->getUser();
        if($user){
            $query = $this->em->getRepository(Posts::class)->getPosts();
            $comments = $this->em->getRepository(Comments::class)->searchComments($user->getId());
            $pagination = $paginator->paginate(
                $query, /* query NOT result */
                $request->query->getInt('page', 1), /*page number*/
                2 /*limit per page*/
            );
            return $this->render('dashboard/index.html.twig', [
                'pagination' => $pagination,
                'comments'=>$comments
            ]);
        } else {
            return $this->redirectToRoute('app_login');
        }
    }
}
