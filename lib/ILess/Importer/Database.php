<?php
/*
 * This file is part of the ILess
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Database importer
 *
 * @package ILess
 * @subpackage import
 */
class ILess_Importer_Database extends ILess_Configurable implements ILess_ImporterInterface
{
    /**
     * The PDO object
     *
     * @var PDO
     */
    protected $dbh;

    /**
     * Array of options
     *
     * @var array
     */
    protected $defaultOptions = array(
        'table_name' => 'stylesheet',
        'filename_column' => 'filename',
        'data_column' => 'data',
        'updated_at_column' => 'updated_at'
    );

    /**
     * Constructor
     *
     * @param array $importPaths Array of import paths to search
     */
    public function __construct(PDO $dbh, $options = array())
    {
        $this->dbh = $dbh;
        parent::__construct($options);
    }

    /**
     * @see ILess_ImporterInterface::import
     */
    public function import($path, ILess_FileInfo $currentFileInfo)
    {
        return $this->find($path, $currentFileInfo);
    }

    /**
     * @see ILess_Importer::getLastModified
     */
    public function getLastModified($path, ILess_FileInfo $currentFileInfo)
    {
        $stmt = $this->dbh->prepare(sprintf('SELECT %s FROM %s WHERE %s = ?',
            $this->getOption('updated_at_column'),
            $this->getOption('table_name'),
            $this->getOption('filename_column')
        ));

        $stmt->execute(array(
            $path
        ));

        $result = $stmt->fetch(PDO::FETCH_COLUMN);

        return ($result !== false) ? intval($result) : 0;
    }

    /**
     * Tries to find a file
     *
     * @param string $path The path to a file
     * @param ILess_FileInfo $currentFileInfo
     * @return string|false
     */
    protected function find($path, ILess_FileInfo $currentFileInfo)
    {
        $stmt = $this->dbh->prepare(sprintf('SELECT %s, %s FROM %s WHERE %s = ?',
            $this->getOption('data_column'),
            $this->getOption('updated_at_column'),
            $this->getOption('table_name'),
            $this->getOption('filename_column')
        ));

        $stmt->execute(array(
            $path
        ));

        $result = $stmt->fetch(PDO::FETCH_NUM);

        if ($result !== false) {
            return new ILess_ImportedFile($path, $result[0], $result[1]);
        }

        return false;
    }

}
