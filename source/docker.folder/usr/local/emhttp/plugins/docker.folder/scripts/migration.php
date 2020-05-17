<?php
    require_once("/usr/local/emhttp/plugins/docker.folder/include/folderVersion.php");

    $path = '/boot/config/plugins/docker.folder/';
    $foldersFile = $path.'folders.json';
    $folders_file = file_get_contents($path.'folders.json');

    if ( file_exists($foldersFile ) ) {
        if (isset($_POST['importFolder'])) {
            init($path, $folders_file, $_POST['importFolder'], true);
        } else {
            init($path, $folders_file, $folders_file, false);
        }
    }

    function init($path, $folders_file, $import, $isImport) {
        
        $folders = json_decode($import, true);

        // exit if there are no folders
            if (count($folders) == null || count($folders) < 2) {
            finish($path, $folders, $folders_file, $isImport);
            exit();
        }

        file_put_contents($path.'folders.backup.json', $folders_file);

        if ($folders['foldersVersion'] == null) {
            $folders = migration_1($folders);
        }
        if ($folders['foldersVersion'] < 2.1) {
            $folders = migration_2($folders);
        }
        if ($folders['foldersVersion'] < 2.2) {
            $folders = migration_3($folders);
        }
        if ($folders['foldersVersion'] < 2.3) {
            $folders = migration_4($folders);
        }

        finish($path, $folders, $folders_file, $isImport);
        
    }

    function finish($path, $folders, $folders_file, $isImport) {
        $folders['foldersVersion'] = $GLOBALS['foldersVersion'];

        if ($isImport) {
            unset($folders['foldersVersion']);
            $currentFolders = json_decode($folders_file, true);
            $output = (object) array_merge((array) $currentFolders, (array) $folders);
        } else {
            $output = $folders;
        }

        $jsonData = json_encode($output, JSON_PRETTY_PRINT);
        file_put_contents($path.'folders.json', $jsonData);
    }

    function migration_1($folders) {
        echo("migration_1");
        foreach ($folders as $folderKey => &$folder) {
            if($folderKey == 'foldersVersion');
            foreach ($folder['buttons'] as $buttonKey => &$button) {

                // if its got type just skip
                if($button['type'] !== null) {
                    continue;
                }

                $isBash = true;

                // WebUI
                if ($button['name'] == 'WebUI') {
                    $isBash = false;

                    $button['type'] = 'WebUI';
                }

                // Docker_Default
                if ($button['cmd'] == 'Docker_Default') {
                    $isBash = false;

                    $button['type'] = 'Docker_Default';
                    $button['cmd'] = strtolower($button['name']);
                } 
                
                // bash
                if ($isBash == true) {
                    $button['type'] = 'Bash';
                }
            }
        }

        return $folders;
    }

    function migration_2($folders) {
        echo("migration_2");
        foreach ($folders as $folderKey => &$folder) {
            if($folderKey == 'foldersVersion') {continue;};
            foreach ($folder['buttons'] as $buttonKey => &$button) {
                // Docker_Sub_Menu set cmd val = name val
                if ($button['type'] == 'Docker_Sub_Menu') {
                    $button['cmd'] = $button['name'];
                }
            }
        }

        return $folders;
    }

    function migration_3($folders) {
        echo("migration_3");
        foreach ($folders as $folderKey => &$folder) {
            if($folderKey == 'foldersVersion') {continue;};
            // remove hidden docker
            exec("docker rm $folderKey-folder");
            
            // remove 'id' key
            unset($folder['id']);
        }

        // remove tianon/true docker image (goodbye old friend ♥)
        exec("docker images -a | grep 'tianon/true' | awk '{print $3}' | xargs docker rmi");

        return $folders;
    }

    function migration_4($folders) {
        echo("migration_4");
        foreach ($folders as $folderKey => &$folder) {
            if($folderKey == 'foldersVersion') {continue;};
            // add docker_expanded_style
            $folder['docker_expanded_style'] = 'bottom';

            // rename start_expanded
            $folder['docker_start_expanded'] = $folder['start_expanded'];
            unset($folder['start_expanded']);
        }

        return $folders;
    }