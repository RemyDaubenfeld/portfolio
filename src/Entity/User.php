<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Table(name: 'user')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[Vich\Uploadable]
class User
{
    #[ORM\Column(name: "id")]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    private ?int $id = null;

    #[ORM\Column(name: "first_name", length: 100, options: ["default" => 'Rémy'])]
    private ?string $firstName = 'Rémy';

    #[ORM\Column(name: "last_name", length: 100, options: ["default" => 'DAUBENFELD'])]
    private ?string $lastName = 'DAUBENFELD';

    #[ORM\Column(name: "job_title", length: 200, options: ["default" => 'Développeur Web Junior'])]
    private ?string $jobTitle = 'Développeur Web Junior';

    #[ORM\Column(name: "hero_tagline", type: Types::TEXT, nullable: true)]
    private ?string $heroTagline = null;

    #[ORM\Column(name: "bio_headline", type: Types::TEXT, nullable: true)]
    private ?string $bioHeadline = null;

    #[ORM\Column(name: "bio_background", type: Types::TEXT, nullable: true)]
    private ?string $bioBackground = null;

    #[ORM\Column(name: "bio_objective", type: Types::TEXT, nullable: true)]
    private ?string $bioObjective = null;

    #[ORM\Column(name: "availability", type: Types::TEXT, nullable: true)]
    private ?string $availability = null;

    #[ORM\Column(name: "contract_type", length: 100, nullable: true)]
    private ?string $contractType = null;

    #[ORM\Column(name: "situation", length: 255, nullable: true)]
    private ?string $situation = null;

    #[ORM\Column(name: "interests", type: Types::JSON, nullable: true)]
    private ?array $interests = null;

    #[Assert\Email(message: 'Adresse email invalide.')]
    #[ORM\Column(name: "email", length: 180, options: ["default" => 'contact@remy-daubenfeld.fr'])]
    private ?string $email = 'contact@remy-daubenfeld.fr';

    #[ORM\Column(name: "phone", length: 20, nullable: true, options: ["default" => '0602719321'])]
    private ?string $phone = '0602719321';

    #[ORM\Column(name: "location", length: 255, nullable: true, options: ["default" => '57160 Moulins-lès-Metz'])]
    private ?string $location = '57160 Moulins-lès-Metz';

    #[ORM\Column(name: "map_url", length: 255, nullable: true, options: ["default" => 'https://www.google.com/maps/place/57160+Moulins-l%C3%A8s-Metz/@49.0929105,6.0985591,14z/'])]
    private ?string $mapUrl = 'https://www.google.com/maps/place/57160+Moulins-l%C3%A8s-Metz/@49.0929105,6.0985591,14z/';

    #[ORM\Column(name: "linkedin_url", length: 255, nullable: true, options: ["default" => 'https://www.linkedin.com/in/remydaubenfeld'])]
    private ?string $linkedinUrl = 'https://www.linkedin.com/in/remydaubenfeld';

    #[ORM\Column(name: "github_url", length: 255, nullable: true, options: ["default" => 'https://github.com/RemyDaubenfeld/'])]
    private ?string $githubUrl = 'https://github.com/RemyDaubenfeld/';

    #[ORM\Column(name: "website_url", length: 255, nullable: true, options: ["default" => 'https://remy-daubenfeld.fr'])]
    private ?string $websiteUrl = 'https://remy-daubenfeld.fr';

    
    #[ORM\Column(name: "photo_file", length: 255, nullable: true, options: ["default" => ''])]
    private ?string $photoFile = '';

    #[Assert\Image(maxSize: '5M')]
    #[Vich\UploadableField(mapping: 'media', fileNameProperty: 'photoFile')]
    private ?File $photoFileFile = null;

    // Chemin stocké en base (ex: CV_DAUBENFELD_Rémy.pdf)
    #[ORM\Column(name: "cv_file", length: 255, nullable: true, options: ["default" => 'CV_DAUBENFELD_Rémy.pdf'])]
    private ?string $cvFile = 'CV_DAUBENFELD_Rémy.pdf';

    #[Assert\File(maxSize: '10M', mimeTypes: ['application/pdf'], mimeTypesMessage: 'Le CV doit être un fichier PDF.')]
    #[Vich\UploadableField(mapping: 'documents', fileNameProperty: 'cvFile')]
    private ?File $cvFileFile = null;

    #[ORM\Column(name: "contact_tagline", type: Types::TEXT, nullable: true)]
    private ?string $contactTagline = null;

    #[ORM\Column(name: "updated_at", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int { return $this->id; }

    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $firstName): static { $this->firstName = $firstName; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $lastName): static { $this->lastName = $lastName; return $this; }

    public function getJobTitle(): ?string { return $this->jobTitle; }
    public function setJobTitle(string $jobTitle): static { $this->jobTitle = $jobTitle; return $this; }

    public function getHeroTagline(): ?string { return $this->heroTagline; }
    public function setHeroTagline(?string $heroTagline): static { $this->heroTagline = $heroTagline; return $this; }

    public function getBioHeadline(): ?string { return $this->bioHeadline; }
    public function setBioHeadline(?string $bioHeadline): static { $this->bioHeadline = $bioHeadline; return $this; }

    public function getBioBackground(): ?string { return $this->bioBackground; }
    public function setBioBackground(?string $bioBackground): static { $this->bioBackground = $bioBackground; return $this; }

    public function getBioObjective(): ?string { return $this->bioObjective; }
    public function setBioObjective(?string $bioObjective): static { $this->bioObjective = $bioObjective; return $this; }

    public function getAvailability(): ?string { return $this->availability; }
    public function setAvailability(?string $availability): static { $this->availability = $availability; return $this; }

    public function getContractType(): ?string { return $this->contractType; }
    public function setContractType(?string $contractType): static { $this->contractType = $contractType; return $this; }

    public function getSituation(): ?string { return $this->situation; }
    public function setSituation(?string $situation): static { $this->situation = $situation; return $this; }

    public function getInterests(): ?array { return $this->interests; }
    public function setInterests(array $interests): static { $this->interests = $interests; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = $phone; return $this; }

    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): static { $this->location = $location; return $this; }

    public function getMapUrl(): ?string { return $this->mapUrl; }
    public function setMapUrl(?string $mapUrl): static { $this->mapUrl = $mapUrl; return $this; }

    public function getLinkedinUrl(): ?string { return $this->linkedinUrl; }
    public function setLinkedinUrl(?string $linkedinUrl): static { $this->linkedinUrl = $linkedinUrl; return $this; }

    public function getGithubUrl(): ?string { return $this->githubUrl; }
    public function setGithubUrl(?string $githubUrl): static { $this->githubUrl = $githubUrl; return $this; }

    public function getWebsiteUrl(): ?string { return $this->websiteUrl; }
    public function setWebsiteUrl(?string $websiteUrl): static { $this->websiteUrl = $websiteUrl; return $this; }

    public function getPhotoFile(): ?string { return $this->photoFile; }
    public function setPhotoFile(?string $photoFile): static { $this->photoFile = $photoFile; return $this; }

    public function getPhotoFileFile(): ?File { return $this->photoFileFile; }
    public function setPhotoFileFile(?File $photoFileFile = null): static
    {
        $this->photoFileFile = $photoFileFile;
        if ($photoFileFile !== null) {
            $this->updatedAt = new \DateTime();
        }
        return $this;
    }

    public function getCvFile(): ?string { return $this->cvFile; }
    public function setCvFile(?string $cvFile): static { $this->cvFile = $cvFile; return $this; }

    public function getCvFileFile(): ?File { return $this->cvFileFile; }
    public function setCvFileFile(?File $cvFileFile = null): static
    {
        $this->cvFileFile = $cvFileFile;
        if ($cvFileFile !== null) {
            $this->updatedAt = new \DateTime();
        }
        return $this;
    }

    public function getContactTagline(): ?string { return $this->contactTagline; }
    public function setContactTagline(?string $contactTagline): static { $this->contactTagline = $contactTagline; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
}