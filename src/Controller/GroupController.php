<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\Participant;
use App\Form\GroupType;
use App\Repository\GroupRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/groupe')]
class GroupController extends AbstractController
{
    #[Route('/', name: 'app_group_index', methods: ['GET'])]
    public function index(GroupRepository $groupRepository, PaginatorInterface $paginator, Request $request): Response
    {
    
        $query = $groupRepository->findGroupsForParticipantQueryBuilder($this->getUser());
    
        // Paginate the result
        $groups = $paginator->paginate($query, $request->query->getInt('page', 1), 10);

        return $this->render('group/index.html.twig', [
            'groups' => $groups,
        ]);
    }

    
    #[Route('/ajouter', name: 'app_group_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ParticipantRepository $participantRepository): Response
    {
        $group = new Group();
        $group->setOwner($this->getUser());

        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);
        $participants = $participantRepository->findBy(['isActive'=>true]);
        if ($form->isSubmitted() && $form->isValid()) {

             // Get the selected members from the hidden input
            $selectedMembers = explode(',', $request->request->get('selected_members'));

            // Process the group creation and add selected members
            $group = $form->getData();
            foreach ($selectedMembers as $memberId) {
                $member = $entityManager->getRepository(Participant::class)->find($memberId);
                if ($member) {
                    $group->addMember($member);
                }
            }    
            $entityManager->persist($group);
            $entityManager->flush();

            $this->addFlash('success', 'Le groupe à été crée avec succès !');
            return $this->redirectToRoute('app_group_index');
        }

        return $this->render('group/new.html.twig', [
            'form' => $form->createView(),
            'participants' => $participants,
        ]);
    }

    
    #[Route('/{id}', name: 'app_group_show', methods: ['GET'])]
    public function show(Group $group): Response
    {
        if ($group->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You do not have permission to view this group.');
        }

        return $this->render('group/show.html.twig', [
            'group' => $group,
        ]);
    }
}
