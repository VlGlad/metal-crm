<?php

namespace App\Security\Voter;

use App\Entity\ProductionDocument;
use App\Entity\User;
use App\Enum\DocumentPermission;
use App\Enum\DocumentStatus;
use App\Enum\DocumentType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProductionDocumentVoter extends Voter
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, array_column(DocumentPermission::cases(), 'value'), true)
            && $subject instanceof ProductionDocument;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        /** @var ProductionDocument $document */
        $document = $subject;

        return match ($attribute) {
            DocumentPermission::VIEW->value => $this->canView($document),
            DocumentPermission::CREATE->value => $this->canCreate($document),
            DocumentPermission::EDIT->value => $this->canEdit($document),
            DocumentPermission::APPROVE->value => $this->canApprove($document),
            DocumentPermission::SIGN->value => $this->canSign($document),
            DocumentPermission::DELETE->value => $this->security->isGranted('ROLE_ADMIN'),
            default => false,
        };
    }

    private function canView(ProductionDocument $document): bool
    {
        return match ($document->getType()) {
            DocumentType::KM_PROJECT,
            DocumentType::KMD,
            DocumentType::PRODUCTION_PLAN => $this->security->isGranted('ROLE_PTO')
                || $this->security->isGranted('ROLE_PO')
                || $this->security->isGranted('ROLE_DIRECTOR'),

            DocumentType::INCOMING_CONTROL_ACT,
            DocumentType::NONCONFORMITY_ACT,
            DocumentType::ACCEPTANCE_ACT => $this->security->isGranted('ROLE_OTK')
                || $this->security->isGranted('ROLE_DIRECTOR'),

            DocumentType::ULTRASONIC_TESTING_REPORT => $this->security->isGranted('ROLE_CZL')
                || $this->security->isGranted('ROLE_OTK')
                || $this->security->isGranted('ROLE_DIRECTOR'),

            DocumentType::WAYBILL => $this->security->isGranted('ROLE_SALES')
                || $this->security->isGranted('ROLE_ACCOUNTING')
                || $this->security->isGranted('ROLE_PTO'),

            default => $this->security->isGranted('ROLE_USER'),
        };
    }

    private function canCreate(ProductionDocument $document): bool
    {
        return match ($document->getType()) {
            DocumentType::KM_PROJECT,
            DocumentType::KMD,
            DocumentType::PRODUCTION_PLAN => $this->security->isGranted('ROLE_PTO'),

            DocumentType::MATERIAL_REQUEST => $this->security->isGranted('ROLE_PO')
                || $this->security->isGranted('ROLE_OMTS'),

            DocumentType::INCOMING_CONTROL_ACT,
            DocumentType::NONCONFORMITY_ACT,
            DocumentType::ACCEPTANCE_ACT => $this->security->isGranted('ROLE_OTK'),

            DocumentType::SHIFT_REPORT => $this->security->isGranted('ROLE_CRO')
                || $this->security->isGranted('ROLE_SSC')
                || $this->security->isGranted('ROLE_CPO'),

            DocumentType::WELDING_JOURNAL => $this->security->isGranted('ROLE_SSC'),

            DocumentType::ULTRASONIC_TESTING_REPORT => $this->security->isGranted('ROLE_CZL'),

            DocumentType::PAINTING_JOURNAL => $this->security->isGranted('ROLE_CPO')
                || $this->security->isGranted('ROLE_OTK'),

            DocumentType::WAYBILL => $this->security->isGranted('ROLE_SALES'),

            default => false,
        };
    }

    private function canEdit(ProductionDocument $document): bool
    {
        if ($document->getStatus() === DocumentStatus::APPROVED) {
            return false;
        }

        return $this->canCreate($document);
    }

    private function canApprove(ProductionDocument $document): bool
    {
        return match ($document->getType()) {
            DocumentType::KM_PROJECT,
            DocumentType::KMD,
            DocumentType::PRODUCTION_PLAN => $this->security->isGranted('ROLE_PTO'),

            DocumentType::MATERIAL_REQUEST => $this->security->isGranted('ROLE_OMTS')
                || $this->security->isGranted('ROLE_PO'),

            DocumentType::INCOMING_CONTROL_ACT,
            DocumentType::NONCONFORMITY_ACT,
            DocumentType::ACCEPTANCE_ACT => $this->security->isGranted('ROLE_OTK'),

            DocumentType::ULTRASONIC_TESTING_REPORT => $this->security->isGranted('ROLE_CZL'),

            DocumentType::SHIFT_REPORT => $this->security->isGranted('ROLE_CRO')
                || $this->security->isGranted('ROLE_SSC')
                || $this->security->isGranted('ROLE_CPO'),

            default => false,
        };
    }

    private function canSign(ProductionDocument $document): bool
    {
        return $this->canApprove($document);
    }
}
