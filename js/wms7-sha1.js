/**
 * Description: A JavaScript implementation of the Secure Hash Algorithm, SHA-1, as defined in FIPS PUB 180-1.
 *
 * @category    Wms7-sha1.js
 * @package     WatchMan-Site7
 * @author      Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * @version     2.1a Copyright Paul Johnston 2000 - 2002.
 * @license     Distributed under the BSD License.
 */

/*
 * Configurable variables. You may need to tweak these to be compatible with
 * the server-side, but the defaults work in most cases.
 */

// Hex output format. 0 - lowercase; 1 - uppercase.
var hexcase = 0;
// Base-64 pad character. "=" for strict RFC compliance.
var b64pad = "";
// Bits per input character. 8 - ASCII; 16 - Unicode.
var chrsz = 8;

/*
 * They take string arguments and return either hex encoded strings.
 *
 * @param string key  Key.
 * @param string data Data.
 * @return string $wms7_binb2hex().
 */
function wms7_hex_sha1(s) {
	return wms7_binb2hex( wms7_core_sha1( wms7_str2binb( s ), s.length * chrsz ) );
}
/*
 * They take string arguments and return either base-64 encoded strings.
 *
 * @param string key  Key.
 * @param string data Data.
 * @return string $wms7_binb2b64().
 */
function wms7_b64_sha1(s) {
	return wms7_binb2b64( wms7_core_sha1( wms7_str2binb( s ), s.length * chrsz ) );
}
/*
 * They take string arguments and return either base-64 encoded strings.
 *
 * @param string key  Key.
 * @param string data Data.
 * @return string $wms7_binb2str().
 */
function wms7_str_sha1(s) {
	return wms7_binb2str( wms7_core_sha1( wms7_str2binb( s ), s.length * chrsz ) );
}
/*
 * They take string arguments and return either hex encoded strings.
 *
 * @param string key  Key.
 * @param string data Data.
 * @return string $wms7_binb2hex().
 */
function wms7_hex_hmac_sha1(key, data) {
	return wms7_binb2hex( wms7_core_hmac_sha1( key, data ) );
}
/*
 * They take string arguments and return either base-64 encoded strings.
 *
 * @param string key  Key.
 * @param string data Data.
 * @return string $wms7_binb2b64().
 */
function wms7_b64_hmac_sha1(key, data) {
	return wms7_binb2b64( wms7_core_hmac_sha1( key, data ) );
}
/*
 * They take string arguments and return either base-64 encoded strings.
 *
 * @param string key  Key.
 * @param string data Data.
 * @return string $wms7_binb2str().
 */
function wms7_str_hmac_sha1(key, data) {
	return wms7_binb2str( wms7_core_hmac_sha1( key, data ) );
}
/*
 * Perform a simple self-test to see if the VM is working.
 *
 * @return string $wms7_hex_sha1().
 */
function wms7_sha1_vm_test() {
	return wms7_hex_sha1( "abc" ) == "a9993e364706816aba3e25717850c26c9cd0d89d";
}
/*
 * Calculate the SHA-1 of an array of big-endian words, and a bit length.
 *
 * @return array $Array.
 */
function wms7_core_sha1(x, len) {
	// append padding.
	x[len >> 5] |= 0x80 << ( 24 - len % 32 );

	x[((len + 64 >> 9) << 4) + 15] = len;

	var w = Array( 80 );
	var a = 1732584193;
	var b = -271733879;
	var c = -1732584194;
	var d = 271733878;
	var e = -1009589776;

	x_length = x.length;
	for ( var i = 0; i < x_length; i += 16 ) {
		var olda = a;
		var oldb = b;
		var oldc = c;
		var oldd = d;
		var olde = e;
		for (var j = 0; j < 80; j++) {
			if ( j < 16 ) {
				w[j] = x[i + j];
			} else {
				w[j] = wms7_rol( w[j - 3] ^ w[j - 8] ^ w[j - 14] ^ w[j - 16], 1 );
			}
			var t = wms7_safe_add( wms7_safe_add( wms7_rol( a, 5 ), wms7_sha1_ft( j, b, c, d ) ), wms7_safe_add( wms7_safe_add( e, w[j] ), wms7_sha1_kt( j ) ) );

			e = d;
			d = c;
			c = wms7_rol( b, 30 );
			b = a;
			a = t;
		}
		a = wms7_safe_add( a, olda );
		b = wms7_safe_add( b, oldb );
		c = wms7_safe_add( c, oldc );
		d = wms7_safe_add( d, oldd );
		e = wms7_safe_add( e, olde );
	}
	return Array( a, b, c, d, e );
}
/*
 * Perform the appropriate triplet combination function for the current iteration.
 *
 * @return string $match.
 */
function wms7_sha1_ft(t, b, c, d) {
	if ( t < 20 ) {
		return ( b & c ) | ( ( ~b ) & d );
	}
	if ( t < 40 ) {
		return b ^ c ^ d;
	}
	if ( t < 60 ) {
		return ( b & c ) | ( b & d ) | ( c & d );
	}
	return b ^ c ^ d;
}
/*
 * Determine the appropriate additive constant for the current iteration.
 *
 * @return string $t.
 */
function wms7_sha1_kt(t) {
	return ( t < 20 ) ? 1518500249 : ( t < 40 ) ? 1859775393 : ( t < 60 ) ? -1894007588 : -899497514;
}
/*
 * Calculate the HMAC-SHA1 of a key and some data.
 *
 * @return string $hash.
 */
function wms7_core_hmac_sha1(key, data) {
	var bkey = wms7_str2binb( key );
	if ( bkey.length > 16 ) {
		bkey = wms7_core_sha1( bkey, key.length * chrsz );
	}
	var ipad = Array( 16 ),
		opad = Array( 16 );
	for (var i = 0; i < 16; i++) {
		ipad[i] = bkey[i] ^ 0x36363636;
		opad[i] = bkey[i] ^ 0x5C5C5C5C;
	}
	var hash = wms7_core_sha1( ipad.concat( wms7_str2binb( data ) ), 512 + data.length * chrsz );
	return wms7_core_sha1( opad.concat( hash ), 512 + 160 );
}
/*
 * Add integers, wrapping at 2^32. This uses 16-bit operations internally.
 * to work around bugs in some JS interpreters.
 *
 * @return string $msw.
 */
function wms7_safe_add(x, y) {
	var lsw = ( x & 0xFFFF ) + ( y & 0xFFFF );
	var msw = ( x >> 16 ) + ( y >> 16 ) + ( lsw >> 16 );
	return ( msw << 16 ) | ( lsw & 0xFFFF );
}
/*
 * Bitwise rotate a 32-bit number to the left.
 *
 * @return string $num.
 */
function wms7_rol(num, cnt) {
	return ( num << cnt ) | ( num >>> ( 32 - cnt ) );
}
/*
 * Convert an 8-bit or 16-bit string to an array of big-endian words.
 * In 8-bit function, characters >255 have their hi-byte silently ignored.
 *
 * @return string $bin.
 */
function wms7_str2binb(str) {
	var bin    = Array();
	var mask   = (1 << chrsz) - 1;
	str_length = str.length * chrsz
	for (var i = 0; i < str_length; i += chrsz) {
		bin[i >> 5] |= ( str.charCodeAt( i / chrsz ) & mask ) << ( 32 - chrsz - i % 32 );
	}
	return bin;
}
/*
 * Convert an array of big-endian words to a string.
 *
 * @return string $str.
 */
function wms7_binb2str(bin) {
	var str    = "";
	var mask   = (1 << chrsz) - 1;
	bin_length = bin.length * 32;
	for (var i = 0; i < bin_length; i += chrsz) {
		str += String.fromCharCode( ( bin[i >> 5] >>> ( 32 - chrsz - i % 32 ) ) & mask );
	}
	return str;
}
/*
 * Convert an array of big-endian words to a hex string.
 *
 * @return string $str.
 */
function wms7_binb2hex(binarray) {
	var hex_tab     = hexcase ? "0123456789ABCDEF" : "0123456789abcdef";
	var str         = "";
	binarray_length = binarray.length * 4;
	for (var i = 0; i < binarray_length; i++) {
		str += hex_tab.charAt( ( binarray[i >> 2] >> ( ( 3 - i % 4 ) * 8 + 4 ) ) & 0xF ) + hex_tab.charAt( ( binarray[i >> 2] >> ( ( 3 - i % 4 ) * 8 ) ) & 0xF );
	}
	return str;
}
/*
 * Convert an array of big-endian words to a base-64 string.
 *
 * @return string $str.
 */
function wms7_binb2b64(binarray) {
	var tab         = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
	var str         = "";
	binarray_length = binarray.length * 4;
	for (var i = 0; i < binarray_length; i += 3) {
		var triplet = (((binarray[i >> 2] >> 8 * (3 - i % 4)) & 0xFF) << 16) | (((binarray[i + 1 >> 2] >> 8 * (3 - (i + 1) % 4)) & 0xFF) << 8) | ((binarray[i + 2 >> 2] >> 8 * (3 - (i + 2) % 4)) & 0xFF);
		for (var j = 0; j < 4; j++) {
			if (i * 8 + j * 6 > binarray.length * 32) {
				str += b64pad;
			} else {
				str += tab.charAt( ( triplet >> 6 * ( 3 - j ) ) & 0x3F );
			}
		}
	}
	return str;
}
