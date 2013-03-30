<?php

/*
 * Copyright © 2010 - 2013 Modo Labs Inc. All rights reserved.
 *
 * The license governing the contents of this file is located in the LICENSE
 * file located at the root directory of this distribution. If the LICENSE file
 * is missing, please contact sales@modolabs.com.
 *
 */

/** defined constants returned by authentication actions **/

/** Authentication was successful */
define('AUTH_OK', 1); 

/** Authentication failed (invalid credentials) */
define('AUTH_FAILED', -1); // 

/** Authentication failed (user was not found) */
define('AUTH_USER_NOT_FOUND', -2); 

/** Authentication failed (User is inactive/disabled) */
define('AUTH_USER_DISABLED', -3);

/** Unknown server or i/o error */
define('AUTH_ERROR', -4); // 

/** Requires OAuth Verification code */
define('AUTH_OAUTH_VERIFY', -5);

