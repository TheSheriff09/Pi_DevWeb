<?php

namespace App\Service;

use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class RiskAssessmentService
{
    private $em;
    private $mailer;
    private $projectDir;

    public function __construct(
        EntityManagerInterface $em, 
        MailerInterface $mailer, 
        #[Autowire('%kernel.project_dir%')] string $projectDir
    ) {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->projectDir = $projectDir;
    }

    public function assessUserRisk(Users $user): array
    {
        // 1. Fetch all reclamations where TargetId = User ID
        // Note: Reclamations repository is standard. Using DQL since it's cleaner.
        $query = $this->em->createQuery(
            'SELECT r.description FROM App\Entity\Reclamations r WHERE r.targetId = :userId'
        )->setParameter('userId', $user->getId());
        
        $results = $query->getResult();
        $descriptions = array_column($results, 'description');

        // Execute Python ML
        // If there are no reclamations, risk score is automatically 0
        if (empty($descriptions)) {
            $user->setRiskScore(0);
            $user->setRiskLevel('NORMAL');
            $this->em->flush();
            return ['score' => 0, 'level' => 'NORMAL'];
        }

        $pythonScript = $this->projectDir . '/bin/assess_user_risk.py';
        
        // Resolve Python Executable (Windows/Unix aware)
        $pythonExe = DIRECTORY_SEPARATOR === '\\' ? $this->projectDir . '\.venv\Scripts\python.exe' : $this->projectDir . '/.venv/bin/python';
        if (!file_exists($pythonExe)) {
            $pythonExe = DIRECTORY_SEPARATOR === '\\' ? 'python' : 'python3';
        }

        // Pass JSON array directly via Process input (stdin)
        $process = new Process([$pythonExe, $pythonScript]);
        $process->setInput(json_encode($descriptions));
        $process->run();

        if (!$process->isSuccessful()) {
            // Ignore failure silently but log it in real prod.
            return ['error' => 'ML Engine Failed'];
        }

        $output = $process->getOutput();
        $data = json_decode($output, true);

        if (!$data || !isset($data['status']) || $data['status'] !== 'success') {
            return ['error' => 'Invalid output from ML Engine'];
        }
        
        $oldLevel = $user->getRiskLevel();
        
        $user->setRiskScore((float)$data['score']);
        $user->setRiskLevel($data['level']);
        $this->em->flush();

        return $data;
    }
}
