<?php

namespace App\Controller;

use App\Entity\Comments;
use App\Entity\Posts;
use App\Form\CommentType;
use App\Form\PostType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use function PHPUnit\Framework\throwException;

class PostController extends AbstractController
{
    private $em;

    /**
     * @param $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/register_post', name: 'register_post')]
    public function index(Request $request, SluggerInterface $slugger): Response
    {
        $post = new Posts();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $brochureFile = $form['photo']->getData();
            if ($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();

                try {
                    $brochureFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throwException('There was an error uploading the photo');
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $post->setPhoto($newFilename);
            }
            $user = $this->getUser();
            $post->setUser($user);
            $this->em->persist($post);
            $this->em->flush();
            return $this->redirectToRoute('app_dashboard');
        }
        return $this->render('post/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/view_post/{id}', name: 'view_post')]
    public function viewPost($id, Request $request, PaginatorInterface $paginator) {
        $comment = new Comments();
        $post = $this->em->getRepository(Posts::class)->find($id);
        $queryComments = $this->em->getRepository(Comments::class)->searchPostComments($post->getId());
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $user = $this->getUser();
            $comment->setPosts($post);
            $comment->setUser($user);
            $this->em->persist($comment);
            $this->em->flush();
            $this->addFlash('success', Comments::SUCCESS_COMMENT);
            return $this->redirectToRoute('view_post',['id'=>$post->getId()]);
        }
        $pagination = $paginator->paginate(
            $queryComments, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            20 /*limit per page*/
        );
        return $this->render('post/viewPost.html.twig',[
            'post'=>$post,
            'form'=>$form->createView(),
            'comments'=>$pagination]);
    }

    #[Route('/my_posts', name: 'my_posts')]
    public function myPosts(): Response
    {
        $user = $this->getUser();
        $posts = $this->em->getRepository(Posts::class)->findBy(['user'=>$user]);
        return $this->render('post/myPosts.html.twig', [
            'posts' => $posts
        ]);
    }

    /**
     * @throws \Exception
     */
    #[Route('/Likes',  name: 'Likes', options: ['expose'=>true])]
    public function like(Request $request){
        if($request->isXmlHttpRequest()){
            $user = $this->getUser();
            $id = $request->request->get('id');
            $post = $this->em->getRepository(Posts::class)->find($id);
            $likes = $post->getLikes();
            $likes .= $user->getId().',';
            $post->setLikes($likes);
            $this->em->flush();
            return new JsonResponse(['likes'=>$likes]);
        }else{
            throw new \Exception('Error');
        }
    }
}