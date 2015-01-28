<?php
/*
 * This file is part of the Manuel Aguirre Project.
 *
 * (c) Manuel Aguirre <programador.manuel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ManuelAguirre\Bundle\TranslationBundle\Translation;

use ManuelAguirre\Bundle\TranslationBundle\Entity\TranslationRepository;
use ManuelAguirre\Bundle\TranslationBundle\Translation\Loader\DoctrineLoader;
use Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\Catalogue\DiffOperation;
use Symfony\Component\Translation\Catalogue\MergeOperation;
use Symfony\Component\Translation\Dumper\DumperInterface;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

/**
 * @autor Manuel Aguirre <programador.manuel@gmail.com>
 */
class TranslationManager
{
    /**
     * @var ExtractorInterface
     */
    private $extractor;
    /**
     * @var TranslationLoader
     */
    private $translationLoader;
    /**
     * @var DoctrineLoader
     */
    private $doctrineLoader;
    /**
     * @var DumperInterface
     */
    private $translationDumper;
    /**
     * @var TranslationRepository
     */
    private $translationRepository;
    private $locales;
    private $extractDirs;
    private $translationFilesDirs;
    private $backupDir;
    private $backupDumper;

    function __construct($extractor, $translationLoader, $doctrineLoader, $translationDumper, $translationRepository, $locales, $extractDirs, $translationFilesDirs)
    {
        $this->extractor = $extractor;
        $this->translationLoader = $translationLoader;
        $this->translationDumper = $translationDumper;
        $this->locales = $locales;
        $this->extractDirs = $extractDirs;
        $this->translationFilesDirs = $translationFilesDirs;
        $this->translationRepository = $translationRepository;
        $this->doctrineLoader = $doctrineLoader;
    }

    /**
     * @param mixed $backupDir
     */
    public function setBackupDir($backupDir)
    {
        $this->backupDir = $backupDir;
    }

    /**
     * @param mixed $backupDumper
     */
    public function setBackupDumper($backupDumper)
    {
        $this->backupDumper = $backupDumper;
    }

    public function getUsedMessages()
    {
        $locale = current($this->locales);
        $usedMessages = new MessageCatalogue($locale);

        foreach ($this->extractDirs as $dir) {
            $this->extractor->extract($dir, $usedMessages);
        }

        return $usedMessages;
    }

    protected function loadFileMessages($locale)
    {
        $catalogue = new MessageCatalogue($locale);

        foreach ($this->translationFilesDirs as $dir) {
            $this->translationLoader->loadMessages($dir, $catalogue);
        }

        return $catalogue;
    }

    public function extractToDatabase()
    {
        $usedMessages = $this->getUsedMessages()->all();

        foreach ($this->locales as $locale) {
            $fileMessages = $this->loadFileMessages($locale);
            $forDump = new MessageCatalogue($locale, $usedMessages);

            foreach ($usedMessages as $domain => $items) {
                foreach ($items as $usedCode => $usedValue) {
                    if ($fileMessages->has($usedCode, $domain)) {
                        $forDump->set($usedCode, $fileMessages->get($usedCode, $domain), $domain);
                    }
                }
            }

            $this->translationDumper->dump($forDump);
        }
    }

    public function inactiveUnused()
    {
        $usedMessages = $this->getUsedMessages();

        $bdMessages = $this->doctrineLoader->load(null, 'en');

        $operation = new DiffOperation($bdMessages, $usedMessages);

        foreach ($bdMessages->all() as $domain => $items) {
            if ($obsoletes = $operation->getObsoleteMessages($domain)) {
                $this->translationRepository->inactiveByDomainAndCodes($domain, array_keys($obsoletes));
            }
        }
    }

    public function generateBackup()
    {
        $path = rtrim($this->backupDir, '/') . '/' . date('Y-m-d-H-i-s') . '/backup.%s.php';

        $filesystem = new Filesystem();

        foreach ($this->locales as $locale) {
            $bdMessages = $this->translationLoader->load(null, $locale);

            $output = "<?php\n\nreturn " . var_export($bdMessages->all(), true) . ";\n";

            $filesystem->dumpFile(sprintf($path, $locale), $output);
        }
    }
}