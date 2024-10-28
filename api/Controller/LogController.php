<?php

declare(strict_types=1);

/*
 * This file is part of Contao Manager.
 *
 * (c) Contao Association
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerApi\Controller;

use Contao\ManagerApi\ApiKernel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to handle log files.
 */
class LogController
{
    public const MONOLOG_PATTERN = '/^\[(?<datetime>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d+.*)\] (?<channel>[\w-]+(?:\.[\w-]+)?)\.(?<level>\w+): (?<message>.+)(?: (?<context>(?:\[.*?\]|\{.*?\})))(?: (?<extra>(?:\[.*\]|\{.*\})))\s{0,2}$/';

    private readonly Filesystem $filesystem;

    public function __construct(
        private readonly ApiKernel $kernel,
        Filesystem|null $filesystem = null,
    ) {
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    #[Route(path: '/logs', methods: ['GET'])]
    public function listFiles(): Response
    {
        if (!$this->filesystem->exists($this->kernel->getProjectDir().'/var/logs')) {
            return new JsonResponse([]);
        }

        /** @var Finder $finder */
        $finder = Finder::create()
            ->depth(0)
            ->files()
            ->ignoreDotFiles(true)
            ->name('*.log')
            ->sortByName(true)
            ->in($this->kernel->getProjectDir().'/var/logs')
        ;

        $files = [];

        foreach ($finder as $file) {
            $files[] = [
                'name' => $this->getFilenameWithoutExtension($file),
                'mtime' => \DateTime::createFromFormat('U', (string) $file->getMTime())->format(\DateTime::ATOM),
                'size' => $file->getSize(),
                'lines' => $this->countLines(new \SplFileObject((string) $file)),
            ];
        }

        // Reverse files order to sort by date descending
        return new JsonResponse(array_reverse($files));
    }

    #[Route(path: '/logs/{filename}', methods: ['GET'])]
    public function retrieveFile(string $filename, Request $request): Response
    {
        $file = $this->getFile($filename);

        if ('json' === $request->getPreferredFormat()) {
            return $this->parseJson($file, $request);
        }

        $response = new BinaryFileResponse($file);
        $response->headers->set('Content-Type', 'text/plain');

        return $response;
    }

    #[Route(path: '/logs/{filename}', methods: ['DELETE'])]
    public function deleteFile(string $filename): Response
    {
        $file = $this->getFile($filename);

        $this->filesystem->remove($file->getPathname());

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * Gets absolute path for filename and checks for security and if file exists.
     */
    private function getFile(string $filename): \SplFileObject
    {
        if (str_contains($filename, '/')) {
            throw new BadRequestHttpException(\sprintf('"%s" is not a valid file name.', $filename));
        }

        $path = $this->kernel->getProjectDir().'/var/logs/'.$filename.'.log';

        if (!is_file($path)) {
            throw new NotFoundHttpException(\sprintf('Log file "%s" does not exist.', $filename));
        }

        return new \SplFileObject($path);
    }

    private function parseJson(\SplFileObject $file, Request $request): JsonResponse
    {
        $file->setFlags(\SplFileObject::DROP_NEW_LINE);

        $content = [];
        $total = $this->countLines($file, $skipLastLine);
        $limit = $request->query->getInt('limit', $total);
        $offset = $request->query->getInt('offset');

        $channels = $request->query->has('channels') ? explode(',', $request->query->get('channel')) : null;
        $levels = $request->query->has('levels') ? explode(',', $request->query->get('levels')) : null;

        if ($offset) {
            $file->seek($offset);
        }

        while (!$file->eof() && $limit > 0) {
            if ($skipLastLine && $file->key() === $total) {
                break;
            }

            if (null !== ($line = $this->parseJsonLine($file->fgets(), $channels, $levels))) {
                $content[] = $line;
            }

            --$limit;
        }

        return new JsonResponse(
            [
                'name' => $this->getFilenameWithoutExtension($file),
                'mtime' => \DateTime::createFromFormat('U', (string) $file->getMTime())->format(\DateTime::ATOM),
                'size' => $file->getSize(),
                'lines' => $total,
                'content' => $content,
            ],
        );
    }

    private function parseJsonLine(string $line, array|null $channels = null, array|null $levels = null): array|string|null
    {
        if (!preg_match(self::MONOLOG_PATTERN, $line, $matches)) {
            return $line;
        }

        if ($channels && !\in_array($matches['channel'], $channels, true)) {
            return null;
        }

        if ($levels && !\in_array($matches['level'], $levels, true)) {
            return null;
        }

        $matches['context'] = json_decode(trim($matches['context'] ?? ''), true);
        $matches['extra'] = json_decode(trim($matches['extra'] ?? ''), true);

        return array_intersect_key($matches, array_flip(['datetime', 'channel', 'level', 'message', 'context', 'extra']));
    }

    private function countLines(\SplFileObject $file, &$skipLastLine = false): int
    {
        $skipLastLine = false;
        $file->seek(PHP_INT_MAX);
        $lines = $file->key() + 1;

        $file->seek($file->key());

        if (empty($file->current())) {
            $skipLastLine = true;
            --$lines;
        }

        $file->rewind();

        return $lines;
    }

    /**
     * We use the file name without extension as REST object name, because some
     * hosters block *.log files for security reasons.
     *
     * @param SplFileInfo|\SplFileObject $file
     */
    private function getFilenameWithoutExtension($file): string
    {
        return pathinfo($file->getFilename(), PATHINFO_FILENAME);
    }
}
