<?php
/**
 * Domain Blacklist Service
 * Periodically updates disposable email domains from GitHub repository
 */
class DomainBlacklistService
{
    private $blacklistFile;
    private $allowlistFile;
    private $githubRepoUrl = 'https://raw.githubusercontent.com/disposable-email-domains/disposable-email-domains/master/disposable_email_blocklist.conf';
    
    public function __construct()
    {
        $this->blacklistFile = __DIR__ . '/../../storage/domains_blacklist.json';
        $this->allowlistFile = __DIR__ . '/../../storage/domains_allowlist.json';
        
        // Ensure storage directory exists
        $storageDir = dirname($this->blacklistFile);
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
    }
    
    /**
     * Update domain lists from GitHub repository
     */
    public function updateDomainLists()
    {
        try {
            // Fetch disposable domains from GitHub
            $disposableDomains = $this->fetchDisposableDomains();
            
            // Get current allowlist
            $allowlist = $this->getAllowlist();
            
            // Save updated lists
            $this->saveBlacklist($disposableDomains);
            $this->saveAllowlist($allowlist);
            
            // Update timestamp
            $this->updateLastUpdated();
            
            return [
                'success' => true,
                'blacklisted_count' => count($disposableDomains),
                'allowed_count' => count($allowlist),
                'message' => 'Domain lists updated successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update domain lists: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Fetch disposable email domains from GitHub
     */
    private function fetchDisposableDomains()
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'HFABS-Domain-Checker/1.0'
            ]
        ]);
        
        $domains = file_get_contents($this->githubRepoUrl, false, $context);
        
        if ($domains === false) {
            throw new Exception('Failed to fetch domains from GitHub');
        }
        
        // Convert text to array and clean up
        $domainArray = array_filter(explode("\n", $domains), function($domain) {
            $domain = trim($domain);
            return !empty($domain) && !str_starts_with($domain, '#');
        });
        
        return array_values($domainArray);
    }
    
    /**
     * Get current allowlist of trusted domains
     */
    private function getAllowlist()
    {
        return [
            // Major email providers
            'gmail.com', 'googlemail.com',
            'outlook.com', 'hotmail.com', 'live.com', 'msn.com',
            'yahoo.com', 'ymail.com', 'rocketmail.com',
            
            // Other popular providers
            'icloud.com', 'me.com', 'mac.com',
            'aol.com', 'protonmail.com', 'tutanota.com',
            
            // Philippine educational domains (wildcard handled separately)
            'up.edu.ph', 'dlsu.edu.ph', 'ust.edu.ph', 'admu.edu.ph',
            'pnu.edu.ph', 'feu.edu.ph', 'cebu.edu.ph', 'slsu.edu.ph',
            'msuiit.edu.ph', 'usc.edu.ph', 'xu.edu.ph', 'wvsu.edu.ph',
            'bsu.edu.ph', 'isu.edu.ph', 'msu.edu.ph', 'clsu.edu.ph',
            'pup.edu.ph', 'tips.edu.ph', 'ceu.edu.ph', 'hnu.edu.ph',
            'csu.edu.ph', 'nmsc.edu.ph', 'uvis.edu.ph',
            
            // Philippine government domains (wildcard handled separately)
            'gov.ph', 'dost.gov.ph', 'deped.gov.ph', 'ched.gov.ph',
        ];
    }
    
    /**
     * Save blacklist to file
     */
    private function saveBlacklist($domains)
    {
        $data = [
            'domains' => $domains,
            'last_updated' => date('Y-m-d H:i:s'),
            'source' => $this->githubRepoUrl
        ];
        
        file_put_contents($this->blacklistFile, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    /**
     * Save allowlist to file
     */
    private function saveAllowlist($domains)
    {
        $data = [
            'domains' => $domains,
            'last_updated' => date('Y-m-d H:i:s'),
            'wildcard_domains' => ['.edu.ph', '.gov.ph']
        ];
        
        file_put_contents($this->allowlistFile, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    /**
     * Update last updated timestamp
     */
    private function updateLastUpdated()
    {
        $timestampFile = __DIR__ . '/../../storage/domains_last_updated.txt';
        file_put_contents($timestampFile, date('Y-m-d H:i:s'));
    }
    
    /**
     * Get blacklist from file
     */
    public function getBlacklist()
    {
        if (!file_exists($this->blacklistFile)) {
            return [];
        }
        
        $data = json_decode(file_get_contents($this->blacklistFile), true);
        return $data['domains'] ?? [];
    }
    
    /**
     * Get allowlist from file
     */
    public function getAllowlistFromFile()
    {
        if (!file_exists($this->allowlistFile)) {
            return $this->getAllowlist();
        }
        
        $data = json_decode(file_get_contents($this->allowlistFile), true);
        return $data['domains'] ?? [];
    }
    
    /**
     * Get wildcard domains from file
     */
    public function getWildcardDomains()
    {
        if (!file_exists($this->allowlistFile)) {
            return ['.edu.ph', '.gov.ph'];
        }
        
        $data = json_decode(file_get_contents($this->allowlistFile), true);
        return $data['wildcard_domains'] ?? ['.edu.ph', '.gov.ph'];
    }
    
    /**
     * Check if lists need updating (older than 24 hours)
     */
    public function needsUpdate()
    {
        $timestampFile = __DIR__ . '/../../storage/domains_last_updated.txt';
        
        if (!file_exists($timestampFile)) {
            return true;
        }
        
        $lastUpdated = file_get_contents($timestampFile);
        $lastUpdatedTime = strtotime($lastUpdated);
        $now = time();
        
        // Update if older than 24 hours
        return ($now - $lastUpdatedTime) > 86400;
    }
    
    /**
     * Auto-update if needed
     */
    public function autoUpdateIfNeeded()
    {
        if ($this->needsUpdate()) {
            return $this->updateDomainLists();
        }
        
        return [
            'success' => true,
            'message' => 'Domain lists are up to date'
        ];
    }
}
?>