<?php
/**
 * User: Andreas K.
 * Date: 15.01.18 KW: 3
 */

namespace TechDivision\TdMailredirect\Domain\Model\Dto;


class EmConfiguration implements \TYPO3\CMS\Core\SingletonInterface
{

    /**
     * @var string
     */
    protected $userAgent = '';

    /**
     * @var string
     */
    protected $testerIp = '';

    /**
     * @var string
     */
    protected $redirectRule = '{local}@{domain}.{tld}';

    /**
     * @var string
     */
    protected $subjectTemplate = '{subject}';

    /**
     * Constructor
     * @param array $configuration
     */
    public function __construct(array $configuration = null)
    {
        if (null === $configuration) {
            $configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['td_mailredirect']);
        }

        foreach ($configuration as $key => $value) {
            if (property_exists($this, $key) && !empty($value)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @return string
     */
    public function getRedirectRule(): string
    {
        return $this->redirectRule;
    }

    /**
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @return string
     */
    public function getTesterIp(): string
    {
        return $this->testerIp;
    }

    /**
     * @return string
     */
    public function getSubjectTemplate(): string
    {
        return $this->subjectTemplate;
    }
}