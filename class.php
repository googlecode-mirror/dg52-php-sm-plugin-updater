<?php
/**
 * dG52 PHP SourceMod (SM) Plugin Updater
 *
 * @author Douglas Stridsberg
 * @url http://code.google.com/p/dg52-php-sm-plugin-updater/
 * @email doggie52@gmail.com
 */
/**
 * clsRcon
 * Connects with a Source dedicated server and allows you to execute rcon commands
 *
 * @author Geert Broekmans <php [at] madclog [dot] nl>
 * @copyright 2008 Geert Broekmans
 * @license GNU GPL
 * @version 1.0
 * ========================================================================
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 * ========================================================================
 */
class clsRcon {
    /**
     * Address of the server
     *
     * @var string
     */
    protected $m_sAddress;
    /**
     * Port number of the server
     *
     * @var int
     */
    protected $m_iPort;
    /**
     * rcon password
     *
     * @var string
     */
    protected $m_sPassword;
    /**
     * TCP socket for communication
     *
     * @var object
     */
    protected $m_oSocket = false;
    /**
     * rcon request id
     *
     * @var int
     */
    protected $m_iRequestId = 0;
    /**
     * timeout in usec
     *
     * @var int
     */
    protected $m_iReadTimeout = 150000;

    const SERVERDATA_EXECCOMMAND = 2;
    const SERVERDATA_AUTH = 3;
    const SERVERDATA_RESPONSE_VALUE = 0;
    const SERVERDATA_AUTH_RESPONSE = 2;

    /**
     * __construct
     * Set the variables used to connect
     *
     * @access public
     * @param string $p_sAddress
     * @param int $p_iPort
     * @param string $p_sPassword
     * @return clsRcon
     */
    public function __construct($p_sAddress, $p_iPort, $p_sPassword) {
        $this->m_sAddress = $p_sAddress;
        $this->m_iPort = $p_iPort;
        $this->m_sPassword = $p_sPassword;
    }

    /**
     * __destruct
     * closes the socket
     *
     * @access public
     * @return void
     */
    public function __destruct() {
        if ($this->m_oSocket !== false) {
            socket_close($this->m_oSocket);
            $this->m_oSocket = false;
        }
    }

    /**
     * connect
     * Connects the socket and authenticates with the server
     *
     * @access public
     * @return boolean
     */
    public function connect() {
        // create a socket
        if (($this->m_oSocket = socket_create(AF_INET,SOCK_STREAM, SOL_TCP)) === false) {
            return false;
        }
        // connect it
        if (socket_connect($this->m_oSocket, $this->m_sAddress, $this->m_iPort) === false) {
            $this->m_oSocket = false;
            return false;
        }
        // send authentication request
        $this->rawPacketSend($this->m_sPassword, null, self::SERVERDATA_AUTH);
        // read the response
        $aResult = $this->rawPacketRead();
        // check if we authenticated succesfully
        if ($aResult[0]['CommandResponse'] != self::SERVERDATA_AUTH_RESPONSE) {
            $this->__destruct();
            return false;
        } else {
            return true;
        }
    }

    /**
     * rcon
     * execute an rcon command
     *
     * @access public
     * @param string $p_sCommand
     * @return array
     */
    public function rcon($p_sCommand) {
        // check connection
        if($this->m_oSocket === false) {
            return false;
        }
        $this->rawPacketSend($p_sCommand);

        return $this->rawPacketRead();
    }

    /**
     * rawPacketSend
     * Builds up a packet and sends it to the server
     *
     * @access protected
     * @param string $p_sString1
     * @param string $p_sString2
     * @param int $p_iCommand
     * @return void
     */
    protected function rawPacketSend($p_sString1, $p_sString2 = NULL, $p_iCommand = self::SERVERDATA_EXECCOMMAND) {
        // build the packet backwards
        $sPacket = $p_sString1 . "\x00" . $p_sString2 . "\x00";
        // build the Request ID and Command into the Packet
        $sPacket = pack('VV',++$this->m_iRequestID, $p_iCommand) . $sPacket;
        // add the length
        $sPacket = pack('V',strlen($sPacket)) . $sPacket;
        // send the packet.
        socket_send($this->m_oSocket, $sPacket, strlen($sPacket), 0x00);
    }

    /**
     * rawPacketRead
     * reads and parses the rcon response
     *
     * @access protected
     * @return array
     */
    protected function rawPacketRead() {
        // the packets
        $aPackets = array();
        // our reading socket
        $aRead = array($this->m_oSocket);
        // we need to use a buffer cause sometimes a packet is send over more then 1 'read request'
        $sBuffer = '';
        while (socket_select($aRead, $aWrite = NULL, $aExcept = NULL, 0, $this->m_iReadTimeout)) {
            // get the packet length
            if (strlen($sBuffer) == 0) {
                $aPacketLength = unpack('V1PacketLength', socket_read($aRead[0], 4));
            }

            // read some data
            $sBuffer .= socket_read($aRead[0], $aPacketLength['PacketLength'] - strlen($sBuffer));
            // if the package is complete parse it
            if (strlen($sBuffer) == $aPacketLength['PacketLength']) {
                // read the actuall packet
                $aPacket = unpack('V1RequestID/V1CommandResponse/a*String1/a*String2', $sBuffer);
                $sBuffer = '';

                if (isset($aPackets[$aPacket['RequestID']]) && $aPacket['CommandResponse'] != self::SERVERDATA_AUTH_RESPONSE) {
                    // existing reply, append the data
                    $aPackets[$aPacket['RequestID']]['String1'] .= $aPacket['String1'];
                    $aPackets[$aPacket['RequestID']]['String2'] .= $aPacket['String2'];
                } else {
                    // new reply
                    $aPackets[$aPacket['RequestID']] = $aPacket;
                }
            }
        }
        return array_values($aPackets);
    }
}

/**
 * get_html_data
 * Return the source code of a website, either using cURL or get_file_contents
 *
 * @param string $url The URL of the page to be accessed.
 * @param string $range The range, in bytes, of the page to download. Example: 50-75. (default: NULL)
 */
function get_html_data($url, $range = NULL)
{
	// If cURL exists, make use of range
	if(function_exists('curl_init'))
	{
		$ch = curl_init();
		$timeout = 5;
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
		if($range)
		{
			curl_setopt($ch,CURLOPT_RANGE,$range);
		}
		$data = curl_exec($ch);
		curl_close($ch);
	}
	// Else, fall back to file_get_contents
	else
	{
		$data = file_get_contents($url);
	}
	return $data;
}

?>