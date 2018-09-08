<?php
/**
 * User: Andreas K.
 * Date: 15.01.18 KW: 3
 */

namespace TechDivision\TdMailredirect\Xclass\Mail;


use TechDivision\TdMailredirect\Domain\Model\Dto\Configuration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/** @noinspection LongInheritanceChainInspection */

class MailMessage extends \TYPO3\CMS\Core\Mail\MailMessage
{

    /**
     * @var Configuration
     */
    protected $emConfiguration;
    /**
     * @var array
     */
    protected $originalReceivers = [];

    /**
     * @inheritdoc
     * @throws \InvalidArgumentException
     */
    public function __construct($subject = null, $body = null, $contentType = null, $charset = null)
    {
        $this->emConfiguration = GeneralUtility::makeInstance(Configuration::class);
        parent::__construct($subject, $body, $contentType, $charset);
    }

    /**
     * Override To
     * @inheritdoc
     */
    public function getTo()
    {
        if (!$this->emConfiguration->isRedirectEnabled()) {
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
     * @param array $addresses
     * @return array
     */
    private function getOverrideAddresses(array $addresses): array
    {
        $overrideAddresses = [];
        foreach ($addresses as $email => $name) {
            $overrideEmail = $this->emConfiguration->getOverrideAddress($email);
            // save the email so it can be added to the body
            if ($email !== $overrideEmail) {
                $this->originalReceivers[$email] = $name;
            }
            $overrideAddresses[$overrideEmail] = $name;
        }

        return $overrideAddresses;
    }

    /**
     * @param array $addresses
     * @param $field
     */
    private function writeOverrideAddressesToMail(array $addresses, $field)
    {
        if (!$this->_setHeaderFieldModel($field, $addresses)) {
            $this->getHeaders()->addMailboxHeader($field, $addresses);
        }
    }

    /**
     * Override Cc
     * @inheritdoc
     */
    public function getCc()
    {
        if (!$this->emConfiguration->isRedirectEnabled()) {
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
        if (!$this->emConfiguration->isRedirectEnabled()) {
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
        if (!$this->emConfiguration->isRedirectEnabled()) {
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
        if (!$this->emConfiguration->isRedirectEnabled()) {
            return parent::setSubject($subject);
        }

        $template = GeneralUtility::makeInstance(StandaloneView::class);
        $template->setTemplateSource($this->emConfiguration->getSubjectTemplate());
        $template->assign('subject', $subject);

        $newSubject = $template->render();
        return parent::setSubject($newSubject);
    }
}