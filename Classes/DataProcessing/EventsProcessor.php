<?php

declare(strict_types=1);

namespace Rubeen\DrkDienstabende\DataProcessing;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Rubeen\DrkDienstabende\Domain\EventMapper;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * Loads the JSON from the URL set in the content element and hands the events to Fluid.
 *
 * The element is rendered uncached (COA_INT, see ext_localconf.php), so cancelled or past
 * evenings disappear without clearing the page cache. The JSON itself is cached here.
 */
final class EventsProcessor implements DataProcessorInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** Seconds before the JSON is fetched again. */
    private const FRESH = 900;

    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly FlexFormService $flexFormService,
        private readonly FrontendInterface $cache,
        private readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData): array
    {
        $settings = $this->flexFormService->convertFlexFormContentToArray((string)($processedData['data']['pi_flexform'] ?? ''));
        $events = EventMapper::map($this->load(trim((string)($settings['url'] ?? ''))), new \DateTimeImmutable(), (int)($settings['limit'] ?? 0));
        $months = [];
        foreach ($events as $event) {
            $months[$event['month']][] = $event;
        }
        $processedData['options'] = $settings; // not 'settings': that would hide fluid_styled_content's settings
        $processedData['events'] = $events;
        $processedData['months'] = $months;
        return $processedData;
    }

    private function load(string $url): mixed
    {
        if (!preg_match('#^https?://#i', $url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }
        // Editors set the URL, so the server only fetches from hosts an admin allowed (no SSRF into internal networks).
        $host = rtrim(strtolower((string)parse_url($url, PHP_URL_HOST)), '.');
        $port = parse_url($url, PHP_URL_PORT);
        if (!in_array($host, $this->allowedHosts(), true) || !in_array($port, [null, 80, 443], true)) {
            $this->logger?->warning('Dienstabende: host {host} (port {port}) is not allowed, see allowedHosts in the extension configuration', ['host' => $host, 'port' => $port ?? 'default']);
            return null;
        }
        $key = sha1($url);
        $entry = $this->cache->get($key) ?: null;
        if ($entry !== null && $entry['fetchedAt'] > time() - self::FRESH) {
            return $entry['json'];
        }
        try {
            // No redirects: a redirect could lead past the host check.
            $response = $this->requestFactory->request($url, 'GET', ['timeout' => 3, 'allow_redirects' => false, 'headers' => ['Accept' => 'application/json']]);
            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('HTTP ' . $response->getStatusCode());
            }
            $json = json_decode((string)$response->getBody(), true, 64, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            // Without query string and exception message: both may carry an access token from the URL.
            $this->logger?->warning('Dienstabende: loading {url} failed: {error}', [
                'url' => $host . parse_url($url, PHP_URL_PATH),
                'error' => get_class($e) . ($e->getCode() ? ' ' . $e->getCode() : ''),
            ]);
            // Keep showing the last good data and retry after FRESH, instead of waiting for the timeout on every page view.
            $json = $entry['json'] ?? null;
        }
        $this->cache->set($key, ['fetchedAt' => time(), 'json' => $json], [], 7 * 86400);
        return $json;
    }

    /** @return list<string> */
    private function allowedHosts(): array
    {
        try {
            $list = (string)$this->extensionConfiguration->get('drk_dienstabende', 'allowedHosts');
        } catch (\Throwable) {
            return [];
        }
        return array_map(static fn(string $h): string => rtrim($h, '.'), GeneralUtility::trimExplode(',', strtolower($list), true));
    }
}
