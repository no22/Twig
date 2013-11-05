<?php
class Twig_Environment_Cache_APC extends Twig_Environment
{
    public function loadTemplate($name, $index = null)
    {
        $cls = $this->getTemplateClass($name, $index);

        if (isset($this->loadedTemplates[$cls])) {
            return $this->loadedTemplates[$cls];
        }

        if (!class_exists($cls, false)) {
            if (false === $cache = $this->getCacheFilename($name)) {
                eval('?>'.$this->compileSource($this->getLoader()->getSource($name), $name));
            } else {
                $success = true;
                $reload = $this->isAutoReload();
                if (!$reload) $cachedCode = apc_fetch($cache, $success);
                if ($reload || !$success) {
                    $cachedCode = $this->compileSource($this->getLoader()->getSource($name), $name);
                    $this->writeCacheFile($cache, $cachedCode);
                }
                eval('?>'.$cachedCode);
            }
        }

        if (!$this->runtimeInitialized) {
            $this->initRuntime();
        }

        return $this->loadedTemplates[$cls] = new $cls($this);
    }

    public function clearCacheFiles()
    {
        if (false === $this->cache) {
            return;
        }
        apc_clear_cache('user');
    }

    protected function writeCacheFile($file, $content)
    {
        if (apc_store($file, $content)) return;
        throw new RuntimeException(sprintf('Failed to write apc cache key "%s".', $file));
    }

}