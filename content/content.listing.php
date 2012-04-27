<?php 
/**
 * @package content
 * @author thomas appel <mail@thomas-appel.com>

 * Displays <a href="http://opensource.org/licenses/gpl-3.0.html">GNU Public License</a>
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */ 
require_once(EXTENSIONS . '/filemanager/content/content.settings.php');
require_once(EXTENSIONS . '/filemanager/lib/class.directorytools.php');

Class contentExtensionFilemanagerListing extends contentExtensionFilemanagerSettings {

	public function __construct(&$parent) {
		parent::__construct($parent);
	}

	public function process() {
		parent::process();
		// extend settings;

		$this->_settings = array_merge($this->_settings, Administration::Configuration()->get($this->_settings['type']));
		$this->getDirectoryListing();
	}


	/**
	 * fetch directorytree structure an set it as page output
	 *
	 * @return void
	 */
	public function getDirectoryListing() {
		$tp = $_GET['select']; // if 'select' is set, update information on a specific subdir on the root path

		$dest_path = !isset($tp) ?  $this->get('destination') : '/workspace' . $tp;


		$base_dir = $this->sanitizePath($dest_path);


		if (!is_readable($base_dir) || !is_writable($base_dir)) {

			$this->handleGeneralError(array(
				'error' => array(
					'message' => 'Cannot access {$file}', 
					'context' => array(
						'file' => basename($dest_path)
					)
				)
			));
			return false;
		}

		$count_level_str = substr(substr($dest_path, strlen($this->get('destination'))), 1); 

		$nesting = !isset($tp) ? 0 : strlen($count_level_str) ? sizeof(explode('/', $count_level_str)) : 0;

		$ignore_files =  $this->get('ignore_files');

		$ignore = strlen(trim(Symphony::Configuration()->get('ignore', 'filemanager'))) > 0 ? base64_decode(Symphony::Configuration()->get('ignore', 'filemanager')) : NULL;

		if (!is_null($ignore)) {
			$ignore = explode(' ', ((strlen($ignore) > 0 && strlen($ignore_files) > 0) ? $ignore . ' ' : $ignore) . $ignore_files);
			$ignore = '/(' . implode('|', preg_replace('/(^\/|\/\w+$)/i', '', $ignore)) . ')/i';
		}


		$excl = $this->sanitizePath(explode(',', preg_replace('/\//im', DIRECTORY_SEPARATOR, FILEMANAGER_EXCLUDE_DIRS)));

		array_shift($excl);

		$exclude = array_merge($excl, $this->sanitizePath(explode(',', $this->get('exclude_dirs'))));

		$roots = $this->getRootPaths();
		$roots = sizeof($roots) > 0 ? $roots : NULL;


		try {
			$dirs = new DirectoryTools($base_dir, $ignore, $exclude, $roots, $nesting);
			$this->_Result = $dirs->getDirectoryTree(true);

		} catch (Exception $e) {
			$this->handleGeneralError(array(
				'error' => array(
					'message' => 'Cannot resolve directory structure for {$root}', 
					'context' => array(
						'root' => substr($base_dir, strlen(FILEMANAGER_WORKSPACE) - 9)
					)
				)
			));	
		}

	}

}
