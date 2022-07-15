#!/usr/bin/env python3
'''
Survey the Humanities Commons git repositories active on the WordPress server
and report their status.
'''

import subprocess
import re

REPOS = {
	'aupresses-jobs'                      : '/srv/www/commons/current/web/app/themes/aupresses-jobs',
	'bbPress-Export-and-Import'           : '/srv/www/commons/current/web/app/plugins/bbPress-Export-and-Import',
	'boss-child'                          : '/srv/www/commons/current/web/app/themes/boss-child',
	'bp-attachment-xprofile-field-type'   : '/srv/www/commons/current/web/app/plugins/bp-attachment-xprofile-field-type',
	'bp-block-member'                     : '/srv/www/commons/current/web/app/plugins/bp-block-member',
	'bp-event-organiser'                  : '/srv/www/commons/current/web/app/plugins/bp-event-organiser',
	'bp-group-documents'                  : '/srv/www/commons/current/web/app/plugins/bp-group-documents',
	'buddypress-docs-minor-edit'          : '/srv/www/commons/current/web/app/plugins/buddypress-docs-minor-edit',
	'buddypress-group-email-subscription' : '/srv/www/commons/current/web/app/plugins/buddypress-group-email-subscription',
	'buddypress-messages-spam-blocker'    : '/srv/www/commons/current/web/app/plugins/buddypress-messages-spam-blocker',
	'cbox-mla-blog'                       : '/srv/www/commons/current/web/app/themes/cbox-mla-blog',
	'commentpress-mla'                    : '/srv/www/commons/current/web/app/themes/commentpress-mla',
	'commentpress-mla-digitalpedagogy'    : '/srv/www/commons/current/web/app/themes/commentpress-mla-digitalpedagogy',
	'commentpress-mla-groups'             : '/srv/www/commons/current/web/app/themes/commentpress-mla-groups',
	'commons'                             : '/srv/www/commons/current',
	'elasticpress-buddypress'             : '/srv/www/commons/current/web/app/plugins/elasticpress-buddypress',
	'faculty-child'                       : '/srv/www/commons/current/web/app/themes/faculty-child',
	'hc-auth'                             : '/srv/www/commons/current/web/app/plugins/hc-auth',
	'hc-custom'                           : '/srv/www/commons/current/web/app/plugins/hc-custom',
	'hc-member-profiles'                  : '/srv/www/commons/current/web/app/plugins/hc-member-profiles',
	'hc-notifications'                    : '/srv/www/commons/current/web/app/plugins/hc-notifications',
	'hc-organizations'                    : '/srv/www/commons/current/web/app/plugins/hc-organizations',
	'hc-protect-uploads'                  : '/srv/www/commons/current/web/app/plugins/hc-protect-uploads',
	'hc-provisional-content'              : '/srv/www/commons/current/web/app/plugins/hc-provisional-content',
	'hc-styles'                           : '/srv/www/commons/current/web/app/plugins/hc-styles',
	'hc-suggestions'                      : '/srv/www/commons/current/web/app/plugins/hc-suggestions',
	'humanities-commons'                  : '/srv/www/commons/current/web/app/plugins/humanities-commons',
	'humcore'                             : '/srv/www/commons/current/web/app/plugins/humcore',
	'mla-academic-interests'              : '/srv/www/commons/current/web/app/plugins/mla-academic-interests',
	'mla-admin-bar'                       : '/srv/www/commons/current/web/app/plugins/mla-admin-bar',
	'mla-allowed-tags'                    : '/srv/www/commons/current/web/app/plugins/mla-allowed-tags',
	'mla-login-bar'                       : '/srv/www/commons/current/web/app/plugins/mla-login-bar',
	'plugin-monitor'                      : '/srv/www/commons/current/web/app/plugins/plugin-monitor',
	'sparkpost-bp-mailer'                 : '/srv/www/commons/current/web/app/plugins/sparkpost-bp-mailer',
	'wordpress-sparkpost'                 : '/srv/www/commons/current/web/app/plugins/wordpress-sparkpost',
	'zotpress'                            : '/srv/www/commons/current/web/app/plugins/zotpress'
}

def get_repo_info( dir ) :
	'''
	Get updated status information for a particular repository.

	Args:
		dir: Directory the repository is located in
	
	Returns:
		A dict with repository information:

		{
			'branch'      : The current local branch or None if no repository
			'remote'      : The remote branch being tracked
			'status'      : 'current' | 'ahead' | 'behind' | 'diverged'
			'uncommitted' : bool - are there unstaged changes?
			'untracked'   : bool - are there untracked files?
		}
	'''
	result_dict = {
		'branch'      : 'None',
		'remote'      : 'None',
		'status'      : 'None',
		'diff_main'   : False,
		'uncommitted' : False,
		'untracked'   : False
	}
	try :
		subprocess.run(
			['git', 'fetch'],
			stdout=subprocess.PIPE,
			stderr=subprocess.DEVNULL,
			cwd=dir,
			universal_newlines=True
		)
		git_status = subprocess.run(
			['git', 'status'],
			stdout=subprocess.PIPE,
			stderr=subprocess.DEVNULL,
			cwd=dir,
			universal_newlines=True
		).stdout
		git_diff_main = subprocess.run(
			['git', 'diff', 'origin/main'],
			stdout=subprocess.PIPE,
			stderr=subprocess.DEVNULL,
			cwd=dir,
			universal_newlines=True
		).stdout
	except FileNotFoundError :
		return result_dict
	branch_regex = re.compile( 'On branch (.*)')
	match = branch_regex.search( git_status )
	if match is None :
		result_dict['branch'] = 'HEAD detached'
		return result_dict
	result_dict['branch'] = match.group( 1 )
	remote_regex = re.compile( 'Your branch .*? \'(.*?)\'' )
	match = remote_regex.search( git_status )
	if match is None :
		result_dict['remote'] = 'None'
	else :
		result_dict['remote'] = match.group( 1 )
	
	if git_status.find( 'Your branch is up to date' ) > -1 :
		result_dict['status'] = 'current'
	elif git_status.find( 'Your branch is ahead of' ) > -1 : 
		result_dict['status'] = 'ahead'
	elif git_status.find( 'Your branch is behind' ) > -1 :
		result_dict['status'] = 'behind'
	else :
		result_dict['status'] = 'diverged'
	
	if git_status.find( 'nothing to commit, working tree clean' ) > -1 :
		result_dict['uncommitted'] = False
		result_dict['untracked'] = False
	else:
		if git_status.find( 'Changes to be committed' ) > -1 or git_status.find( 'Changes not staged for commit') > -1 :
			result_dict['uncommitted'] = True
		if git_status.find( 'Untracked files' ) > 1 :
			result_dict['untracked'] = True

	if len( git_diff_main ) > 0 :
		result_dict['diff_main'] = True

	return result_dict

def make_repo_table( repo_dict ) :
	'''
	Construct a table of repo statuses.

	Args:
		repo_dict: Dictionay of repo_name : repo_directory
	
	Returns:
		Array of dicts containing repo statuses
	'''
	repo_table = []
	for name, directory in repo_dict.items() :
		repo_info = get_repo_info( directory )
		repo_info['name'] = name
		repo_table.append( repo_info )
	return repo_table

def print_repo_table( repo_table ) :
	'''
	Outputs a table of repo info to the console.

	Args:
		repo_table: Array of dicts containing repository info
	'''
	print ( '{:37} {:13} {:15} {:8} {:9} {:5} {:5}'.format( 'Name', 'Branch', 'Remote', 'Status', 'Diff Main','UC', 'UT' ) )
	print ( '{:37} {:13} {:15} {:8} {:9} {:5} {:5}'.format( '----', '------', '------', '------', '---------','--', '--' ) )
	for row in repo_table :
		print ( '{:37.37} {:13.13} {:15.15} {:8.8} {:9.9} {:5.5} {:5.5}'.format( 
			row['name'],
			row['branch'],
			row['remote'],
			row['status'],
			str( row['diff_main'] ),
			str( row['uncommitted'] ),
			str( row['untracked'] )
		) )

if __name__ == '__main__' :
	repo_table = make_repo_table( REPOS )
	print_repo_table( repo_table )
