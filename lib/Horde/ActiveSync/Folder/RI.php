<?php
/**
 * Horde_ActiveSync_Folder_RI::
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *
 * @copyright 2014-2026 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
/**
 * The class contains functionality for maintaining state for the
 * Recipient Information Cache.
 *
 * @license   http://www.horde.org/licenses/gpl GPLv2
 *
 * @copyright 2014-2026 Horde LLC (http://www.horde.org)
 * @author    Michael J Rubinsky <mrubinsk@horde.org>
 * @package   ActiveSync
 */
class Horde_ActiveSync_Folder_RI extends Horde_ActiveSync_Folder_Base implements Serializable
{
    const VERSION = 1;

    /**
     * The current list of recipient email addresses.
     *
     * @var array
     */
    protected $_contacts = [];
    protected $_serverid = 'RI';
    protected $_removed = [];
    protected $_added = [];

    /**
     * Set the current Recipient Cache
     *
     * @param array $contacts  An array of email addresses. Ordered by weight.
     */
    public function setChanges(array $contacts)
    {
        $contacts = array_reverse($contacts);

        // Calculate deletions.
        foreach ($this->_contacts as $weight => $email) {
            if (($contacts[$weight] ?? null) !== $email) {
                $this->_removed[] = $email . ':' . $weight;
            }
        }

        // Additions
        foreach ($contacts as $weight => $email) {
            if (($this->_contacts[$weight] ?? null) !== $email) {
                $this->_added[] = $email . ':' . $weight;
            }
        }

        $this->_contacts = $contacts;
    }

    /**
     * Updates the internal UID cache, and clears the internal
     * update/deleted/changed cache.
     */
    public function updateState()
    {
        $this->haveInitialSync = true;
        $this->_removed = [];
        $this->_added = [];
    }

    /**
     * Convert the instance into a string.
     *
     * @return string The string representation for this instance.
     */
    public function __toString()
    {
        return sprintf(
            'serverid: %s\nclass: %s\n',
            $this->serverid(),
            $this->collectionClass());
    }

    /**
     * Return the recipients that are to be added.
     *
     * @return array  An array of psuedo-uids consisting of the the email
     *                address, a colon, and the weighed rank. E.g.
     *                user@example.com:10
     */
    public function added()
    {
        return $this->_added;
    }

    /**
     * Return the recipients that are to be deleted.
     *
     * @return array  An array of psuedo-uids consisting of the the email
     *                address, a colon, and the weighed rank. E.g.
     *                user@example.com:10
     */
    public function removed()
    {
        return $this->_removed;
    }

    /**
     * Serialize this object.
     *
     * @return string  The serialized data.
     */
    public function serialize()
    {
        return json_encode($this->__serialize());
    }

    /**
     * Serialize this object (modern PHP 8.1+ interface).
     *
     * @return array  The data to serialize.
     */
    public function __serialize(): array
    {
        return [
            'd' => $this->_contacts,
            'f' => $this->_serverid,
            'c' => $this->_class,
            'v' => self::VERSION,
        ];
    }

    /**
     * Reconstruct the object from serialized data.
     *
     * @param string $data  The serialized data.
     * @throws Horde_ActiveSync_Exception_StaleState
     */
    public function unserialize($data)
    {
        $data = @json_decode($data, true);
        if (!is_array($data)) {
            throw new Horde_ActiveSync_Exception_StaleState('Cache version change');
        }
        $this->__unserialize($data);
    }

    /**
     * Reconstruct the object from serialized data (modern PHP 8.1+ interface).
     *
     * @param array $data  The unserialized data.
     * @throws Horde_ActiveSync_Exception_StaleState
     */
    public function __unserialize(array $data): void
    {
        if (($data['v'] ?? null) != self::VERSION) {
            throw new Horde_ActiveSync_Exception_StaleState('Cache version change');
        }
        $this->_contacts = $data['d'];
        $this->_serverid = $data['f'];
        $this->_class    = $data['c'];
    }

}
