<?php
/**
 * User: Andreas K.
 * Date: 15.01.18 KW: 3
 */

namespace TechDivision\TdMailredirect\Xclass\Mail;


use TechDivision\TdMailredirect\Domain\Model\Dto\EmConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/** @noinspection LongInheritanceChainInspection */
class MailMessage extends \TYPO3\CMS\Core\Mail\MailMessage
{

    /**
     * @var array
     */
    protected $originalReceivers = [];

    /**
     * @var EmConfiguration
     */
    protected static $emConfiguration;

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function __construct($subject = null, $body = null, $contentType = null, $charset = null)
    {
        static::$emConfiguration = GeneralUtility::makeInstance(EmConfiguration::class);
        parent::__construct($subject, $body, $contentType, $charset);
    }

    /**
     * @return bool
     * @throws \UnexpectedValueException
     */
    private function redirectEnabled():bool
    {
        $userAgent = GeneralUtility::getIndpEnv('HTTP_USER_AGENT');
        $remoteAddress = GeneralUtility::getIndpEnv('REMOTE_ADDR');

        $configuredUserAgent = static::$emConfiguration->getUserAgent();


        if ($configuredUserAgent !== ''
            && $configuredUserAgent !== '*'
            && $configuredUserAgent !== $userAgent) {
            return false;
        }

        if (static::$emConfiguration->getTesterIp()
            && !GeneralUtility::cmpIP($remoteAddress, static::$emConfiguration->getTesterIp())) {
            return false;
        }

        return true;
    }

    /**
     * @param string $email
     * @return string
     */
    private function getOverrideAddress(string $email):string
    {
        $template = GeneralUtility::makeInstance(StandaloneView::class);
        $templateSource = static::$emConfiguration->getRedirectRule();

        $template->setTemplateSource($templateSource);
        $template->assign('email', $email);

        list($local, $fullDomain) = explode('@', $email, 2);
        $domainParts = explode('.', $fullDomain);
        $tld = array_pop($domainParts);
        $domain = implode('.', $domainParts);

        $templateVaraibles = [
            'email' => $email,
            'local' => $local,
            'domain' => $domain,
            'tld' => $tld,
        ];

        $template->assignMultiple($templateVaraibles);

        return $template->render();
    }

    /**
     * @param array $addresses
     * @return array
     */
    private function getOverrideAddresses(array $addresses): array
    {
        $overrideAdresses = [];
        foreach ($addresses as $email => $name) {
            $overrideEmail = $this->getOverrideAddress($email);
            $overrideAdresses[$overrideEmail] = $name;
        }

        return $overrideAdresses;
    }

    /**
     * @param array $addresses
     * @param $field
     */
    private function writeOverrideAddressesToMail(array $addresses, $field)
    {
        $this->originalReceivers = array_merge($this->originalReceivers, $addresses);
        if (!$this->_setHeaderFieldModel($field, $addresses)) {
            $this->getHeaders()->addMailboxHeader($field, $addresses);
        }
    }

    /**
     * Override To
     * @inheritdoc
     */
    public function getTo()
    {
        if (!$this->redirectEnabled()) {
            return parent::getTo();
        }

        $field = 'To';
        $originalRecipients = parent::getTo();

        if (\is_array($originalRecipients) && \count($originalRecipients) > 0) {

            $recipients = $this->getOverrideAddresses($originalRecipients);
            $this->writeOverrideAddressesToMail($recipients, $field);

            return $recipients;
        }

        return [];
    }

    /**
     * Override Cc
     * @inheritdoc
     */
    public function getCc()
    {
        if (!$this->redirectEnabled()) {
            return parent::getCc();
        }

        $originalRecipients = parent::getCc();
        $field = 'Cc';

        if (\is_array($originalRecipients) && \count($originalRecipients) > 0) {
            $recipients = $this->getOverrideAddresses($originalRecipients);
            $this->writeOverrideAddressesToMail($recipients, $field);
            return $recipients;
        }

        return [];
    }

    /**
     * Override Bcc
     * @inheritdoc
     */
    public function getBcc()
    {
        if (!$this->redirectEnabled()) {
            return parent::getBcc();
        }

        $originalRecipients = parent::getBcc();
        $field = 'Bcc';

        if (\is_array($originalRecipients) && \count($originalRecipients) > 0) {
            $recipients = $this->getOverrideAddresses($originalRecipients);
            $this->writeOverrideAddressesToMail($recipients, $field);
            return $recipients;
        }

        return [];
    }


    /**
     * Get the body content of this entity as a string.
     *
     * Returns NULL if no body has been set.
     *
     * @return string|null
     */
    public function getBody()
    {
        if (!$this->redirectEnabled()) {
            return parent::getBody();
        }

        $body = parent::getBody();
        $body .= '<br /><hr /><br />This mail must be sent to : ' . implode(';', array_keys($this->originalReceivers));
        return $body;
    }

    /**
     * Getter is not called, so intercept the call to the setter
     *
     * @param string $subject
     * @inheritdoc
     */
    public function setSubject($subject)
    {
        if (!$this->redirectEnabled()) {
            return parent::setSubject($subject);
        }

        $template = GeneralUtility::makeInstance(StandaloneView::class);
        $template->setTemplateSource(static::$emConfiguration->getSubjectTemplate());
        $template->assign('subject', $subject);

        $newSubject = $template->render();
        return parent::setSubject($newSubject);
    }
}