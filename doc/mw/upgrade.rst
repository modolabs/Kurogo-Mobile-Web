================================
Upgrading from Previous Versions
================================

Kurogo is designed to make upgrades simple and easy. If you follow the prescribed procedure
for site creation and extension, you can update your installation to new Kurogo versions without
overwriting your customizations.

-------------------------------------
If you have forked the git repository
-------------------------------------

Git makes it very easy to implement new changes. You can simply pull down the changes from
the master repository and merge it into your repository. 

#. In your repository, set up an upstream remote: 
   
   * :kbd:`git remote add upstream git://github.com/modolabs/Kurogo-Mobile-Web.git`
   * :kbd:`git fetch upstream`
   * :kbd:`git checkout -b upstream upstream/master`

#. When new changes come down you can run:
   
   * :kbd:`git checkout upstream` Change to upstream branch
   * :kbd:`git pull` Pull down changes
   * :kbd:`git checkout master` Change to master branch
   * :kbd:`git merge upstream` Merge changes into master branch


As long as you have only edited files in your site folder, your merge should apply cleanly.

--------------------------------
If you used a downloaded version
--------------------------------

If you are not using git to manage your code, you can simply download the new version and
simply copy your site folder into the new distribution. Make sure you retain your kurogo.ini
file. 