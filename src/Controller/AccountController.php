<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

class AccountController extends AbstractController
{
    #[Route('/account/password', name: 'account_password')]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em
    ): Response {
        if ($request->isMethod('POST')) {
            // Les actions qui modifient exigent une auth complète
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        } else {
            // Lecture/affichage OK si utilisateur "remembered"
            $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        }

        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $current = (string) $request->request->get('current_password', '');
            $new     = (string) $request->request->get('new_password', '');
            $confirm = (string) $request->request->get('confirm_password', '');

            if (!$hasher->isPasswordValid($user, $current)) {
                $this->addFlash('error', 'Mot de passe actuel incorrect.');
            } elseif (strlen($new) < 8) {
                $this->addFlash('error', 'Nouveau mot de passe trop court (min. 8 caractères).');
            } elseif ($new !== $confirm) {
                $this->addFlash('error', 'La confirmation ne correspond pas.');
            } else {
                $user->setPassword($hasher->hashPassword($user, $new));
                $em->flush();
                $this->addFlash('success', 'Mot de passe mis à jour.');
                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('account/password.html.twig');
    }
}
