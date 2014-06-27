set :application, 'multi-post-thumbnails'
set :repo_url, "git@github.com:voceconnect/#{fetch(:application)}.git"

set :scm, 'git-to-svn'
set :type, 'plugin'

set :svn_repository, "http://plugins.svn.wordpress.org/multiple-post-thumbnails/"
set :svn_deploy_to, "trunk"

set :build_folders, (
  fetch(:build_folders) << %w{
    *config
  }
).flatten