<?php
/*
  Copyright (c) 2020, Rainer Schmied
  All rights reserved.

  Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

  1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.

  2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

  3. Neither the name of the copyright holder nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.

  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

class hook_admin_system_seoUrl {
  var $version = '1.0.0';
  
}

// Function to reset SEO URLs database cache entries
// Ultimate SEO URLs v2.2d
function tep_reset_cache_data_seo_urls($action){        
  switch ($action){
    case 'reset':
    case 'uninstall':
       tep_db_query("DELETE FROM cache WHERE cache_name LIKE '%seo_urls%'");
       tep_db_query("UPDATE configuration SET configuration_value='false' WHERE configuration_key='SEO_URLS_CACHE_RESET'");

       if ($action == 'reset') break;
    
       tep_db_query("DELETE FROM configuration_group WHERE configuration_group_title LIKE '%seo_urls%'");
	     tep_db_query("DELETE FROM configuration WHERE configuration_key LIKE 'SEO%' OR configuration_key LIKE 'USE_SEO%'");
    break;    
    default:
    break;
  }
  # The return value is used to set the value upon viewing
  # It's NOT returining a false to indicate failure!!
  return 'false';
}
