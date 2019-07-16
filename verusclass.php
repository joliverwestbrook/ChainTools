<?php
/**
 * VerusChainTools
 * 
 * Description: A toolkit for interacting with Verus and Verus PBaaS blockchains, 
 * allowing websites to access the daemon RPC via PHP for a more secure and 
 * flexible integration. VerusChainTools works with VerusCoin, PBaaS by Verus 
 * chains, Komodo and Komodo asset chains, and any Verus, Komodo, Zcash, or 
 * Bitcoin fork with minimal adaptation.
 * 
 * Included files:
 *      index.php
 *      verusclass.php (this file)
 *      install.php (temporary installer)
 *      demo.php
 *
 * @category Cryptocurrency
 * @package  VerusChainTools
 * @author   Oliver Westbrook <johnwestbrook@pm.me>
 * @copyright Copyright (c) 2019, John Oliver Westbrook
 * @link     https://github.com/joliverwestbrook/VerusChainTools
 * @version 0.4.0-rc
 * 
 * ====================
 * 
 * The MIT License (MIT)
 * 
 * Copyright (c) 2019 John Oliver Westbrook
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * 
 * ====================
 * 
 */
// Begin Class Code
class Verus {
    private $u;
    private $p;
    private $pr;
    private $h;
    private $po;
    private $c;

    // Information and debugging
    public $sts;
    public $err;
    public $raw;
    public $ret;
    private $id = 0;
    /**
     * @param string $u
     * @param string $p
     * @param string $h
     * @param int $po
     * @param string $pr
     * @param string $u
     */
    public function __construct( $u, $p, $h, $po, $pr ) {
        $this->u    = $u;
        $this->p    = $p;
        $this->h    = $h;
        $this->po   = $po;
        $this->pr   = $pr;
        $this->c    = null;
    }
    /**
     * @param string|null $cer
     */
    public function setSSL( $cer = null ) {
        $this->pr         = 'https';
        $this->c = $cer;
    }
    public function __call( $mth, $par ) {
        $this->sts = null;
        $this->err = null;
        $this->raw = null;
        $this->ret = null;
	    $this->par = array();
	    if ( !empty( $par[0] ) ) {
	        if ( $par[0][0] === '[' ) {
		        $this->par = json_decode( $par[0], TRUE );
	        }
	        else {
		        $this->par = array( json_decode( $par[0], TRUE ) );
	        }
        }
	    $this->par = array_values( $this->par );
        $this->id++;
        $req = json_encode( array(
            'method' => $mth,
            'params' => $this->par,
            'id'     => $this->id
        ) );
        // TODO : Test area
        //return $req;
        //die();
        // END
        $cur    = curl_init( "{$this->pr}://{$this->h}:{$this->po}" );
        $opt = array(
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_USERPWD        => $this->u . ':' . $this->p,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_HTTPHEADER     => array( 'Content-type: application/json' ),
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $req
        );
        if ( ini_get( 'open_basedir' ) ) {
            unset( $opt[CURLOPT_FOLLOWLOCATION] );
        }
        if ( $this->pr == 'https' ) {
            if ( ! empty( $this->c ) ) {
                $opt[CURLOPT_CAINFO] = $this->c;
                $opt[CURLOPT_CAPATH] = DIRNAME( $this->c );
            } else {
                $opt[CURLOPT_SSL_VERIFYPEER] = false;
            }
        }
        curl_setopt_array( $cur, $opt );
        $this->raw = curl_exec( $cur );
        $this->ret = json_decode( $this->raw, true );
        $this->sts = curl_getinfo( $cur, CURLINFO_HTTP_CODE );
        $cre = curl_error( $cur );
        curl_close( $cur );
        if ( ! empty( $cre ) ) {
            $this->err = $cre;
        }
        if ( $this->ret['error'] ) {
            $this->err = $this->ret['error']['message'];
        } elseif ( $this->sts != 404 ) {
            switch ( $this->sts ) {
		case 0:
		    $this->err = 'Offline';
		    break;
                case 400:
                    $this->err = '400';
                    break;
                case 401:
                    $this->err = '401';
                    break;
                case 403:
                    $this->err = '403';
                    break;
            }
        }
        if ( $this->err ) {
            return $this->err;
        }
        return $this->ret['result'];
    }
}
