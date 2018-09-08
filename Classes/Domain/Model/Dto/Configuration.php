<?php
/**
 * User: Andreas K.
 * Date: 15.01.18 KW: 3
 */

namespace TechDivision\TdMailredirect\Domain\Model\Dto;


use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class Configuration implements \TYPO3\CMS\Core\SingletonInterface
{

    /** @var string */
    protected $userAgent = '';

    /** @var string */
    protected $testerIp = '';

    /** @var string */
    protected $redirectRule = '{local}@{domain}.{tld}';

    /** @var string */
    protected $subjectTemplate = '{subject}';

    /** @var string */
    protected $whitelistedAddresses = '';

    /** @var array */
    protected $whitelistedAddressesArray = [];

    /**
     * Constructor
     * @param array $configuration
     */
    public function __construct(array $configuration = null)
    {
        if (null === $configuration) {
            $configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['td_mailredirect'], ['allowed_classes' => false]);
        }

        foreach ($configuration as $key => $value) {
            $value = trim($value);
            if (property_exists($this, $key) && !empty($value)) {
                $this->$key = $value;
            }
        }

        $this->whitelistedAddressesArray = GeneralUtility::trimExplode(',', $this->whitelistedAddresses);
    }

    /**
     * @return string
     */
    public function getSubjectTemplate(): string
    {
        return $this->subjectTemplate;
    }

    /**
     * @return string
     */
    public function getRedirectRule(): string
    {
        return $this->redirectRule;
    }

    /**
     * @return bool
     * @throws \UnexpectedValueException
     */
    public function isRedirectEnabled(): bool
    {
        // we only support redirecting mails from frontend requests
        if (false === $this->isFrontendRequest()) {
            return false;
        }

        $remoteAddress = GeneralUtility::getIndpEnv('REMOTE_ADDR');
        $configuredTesterIp = $this->getTesterIp();
        if (false === $this->isRequestFromAllowedIp($remoteAddress, $configuredTesterIp)) {
            return false;
        }

        $userAgent = GeneralUtility::getIndpEnv('HTTP_USER_AGENT');
        $configuredUserAgent = $this->getUserAgent();
        if (false === $this->isRequestFromAllowedUserAgent($userAgent, $configuredUserAgent)) {
            return false;
        }

        return true;
    }

    /**
     * @param string $email
     * @param array $whitelistedAddresses
     * @return bool
     */
    public function isEmailAddressWhitelisted(string $email, array $whitelistedAddresses): bool
    {
        if (\count($whitelistedAddresses) === 0) {
            return false;
        }
        return \count(array_filter($whitelistedAddresses, function ($entry) use ($email) {
            return fnmatch($entry, $email);
        })) > 0;
    }

    /**
     * @return bool
     */
    private function isFrontendRequest(): bool
    {
        return (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_FE) || (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX);
    }

    /**
     * @return string
     */
    public function getTesterIp(): string
    {
        return $this->testerIp;
    }

    /**
     * @param string $remoteAddress
     * @param string $configuredTesterIp
     * @return bool
     */
    public function isRequestFromAllowedIp($remoteAddress, $configuredTesterIp): bool
    {
        if (empty($configuredTesterIp)) {
            return false;
        }

        if ($configuredTesterIp === '*') {
            return true;
        }

        return GeneralUtility::cmpIP($remoteAddress, $configuredTesterIp);
    }

    /**
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @param string $userAgent
     * @param string $configuredUserAgent
     * @return bool
     */
    public function isRequestFromAllowedUserAgent($userAgent, $configuredUserAgent): bool
    {
        if (empty($configuredUserAgent)) {
            return false;
        }

        if ($configuredUserAgent === '*') {
            return true;
        }

        return $configuredUserAgent === $userAgent;
    }

    public function getOverrideAddress(string $email)
    {
        if ($this->isEmailAddressWhitelisted($email, $this->whitelistedAddressesArray)){
            return $email;
        }

        $template = GeneralUtility::makeInstance(StandaloneView::class);
        $templateSource = $this->getRedirectRule();

        $template->setTemplateSource($templateSource);
        $template->assign('email', $email);

        list($local, $fullDomain) = explode('@', $email, 2);
        $domainParts = explode('.', $fullDomain);
        $tld = array_pop($domainParts);
        $domain = implode('.', $domainParts);

        $templateVariables = [
            'email' => $email,
            'local' => $local,
            'domain' => $domain,
            'tld' => $tld,
        ];

        $template->assignMultiple($templateVariables);

        return $template->render();
    }


}