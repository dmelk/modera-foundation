<?php

namespace Modera\ConfigBundle\Controller;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractSettingsController extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    abstract protected function getRole(): string;

    abstract protected function getFilename(): string;

    abstract protected function exportData(): array;

    abstract protected function toYaml(array $data): string;

    abstract protected function importData(array $data): void;

    protected function handleExport(): Response
    {
        $this->denyAccessUnlessGranted($this->getRole());

        $data = $this->exportData();
        $yaml = $this->toYaml($data);

        $response = new Response(
            content: $yaml,
            status: Response::HTTP_OK,
            headers: [
                'Content-Type' => 'text/yaml; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache',
            ],
        );

        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $this->getFilename()),
        );

        return $response;
    }

    protected function handleImport(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted($this->getRole());

        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['success' => false, 'error' => 'No file provided.'], 400);
        }

        try {
            $content = file_get_contents($file->getPathname());
            $data = Yaml::parse($content);

            if (!is_array($data)) {
                return $this->json(['success' => false, 'error' => 'Invalid YAML structure.'], 400);
            }

            $this->importData($data);
        } catch (ParseException $e) {
            return $this->json(['success' => false, 'error' => 'Invalid YAML: ' . $e->getMessage()], 400);
        } catch (\Throwable $e) {
            $this->logger?->error('Settings import failed', ['exception' => $e]);
            return $this->json(['success' => false, 'error' => 'Import failed due to a server error.'], 500);
        }

        return $this->json(['success' => true]);
    }
}