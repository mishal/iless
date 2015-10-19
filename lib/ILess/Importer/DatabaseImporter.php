<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ILess\Importer;

use ILess\Configurable;
use ILess\FileInfo;
use ILess\ImportedFile;
use PDO;

/**
 * Database importer.
 */
class DatabaseImporter extends Configurable implements ImporterInterface
{
    /**
     * The PDO object.
     *
     * @var PDO
     */
    protected $dbh;

    /**
     * Array of options.
     *
     * @var array
     */
    protected $defaultOptions = [
        'table_name' => 'stylesheet',
        'filename_column' => 'filename',
        'data_column' => 'data',
        'updated_at_column' => 'updated_at',
    ];

    /**
     * Constructor.
     *
     * @param array $importPaths Array of import paths to search
     */
    public function __construct(PDO $dbh, $options = [])
    {
        $this->dbh = $dbh;
        parent::__construct($options);
    }

    /**
     * @see ImporterInterface::import
     */
    public function import($path, FileInfo $currentFileInfo)
    {
        return $this->find($path, $currentFileInfo);
    }

    /**
     * @see Importer::getLastModified
     */
    public function getLastModified($path, FileInfo $currentFileInfo)
    {
        $stmt = $this->dbh->prepare(sprintf('SELECT %s FROM %s WHERE %s = ?',
            $this->getOption('updated_at_column'),
            $this->getOption('table_name'),
            $this->getOption('filename_column')
        ));

        $stmt->execute([
            $path,
        ]);

        $result = $stmt->fetch(PDO::FETCH_COLUMN);

        return ($result !== false) ? intval($result) : 0;
    }

    /**
     * Tries to find a file.
     *
     * @param string $path The path to a file
     * @param FileInfo $currentFileInfo
     *
     * @return string|false
     */
    protected function find($path, FileInfo $currentFileInfo)
    {
        $stmt = $this->dbh->prepare(sprintf('SELECT %s, %s FROM %s WHERE %s = ?',
            $this->getOption('data_column'),
            $this->getOption('updated_at_column'),
            $this->getOption('table_name'),
            $this->getOption('filename_column')
        ));

        $stmt->execute([
            $path,
        ]);

        $result = $stmt->fetch(PDO::FETCH_NUM);

        if ($result !== false) {
            return new ImportedFile($path, $result[0], $result[1]);
        }

        return false;
    }
}
