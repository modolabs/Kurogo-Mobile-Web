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

#. When new changes come down you can run:
   
   * :kbd:`git fetch upstream`
   * :kbd:`git merge upstream/master` Merge changes into your master branch


As long as you have only edited files in your site folder, your merge should apply cleanly.

--------------------------------
If you used a downloaded version
--------------------------------

If you are not using git to manage your code, you can simply download the new version and
simply copy your site folder into the new distribution. Make sure you retain your kurogo.ini
file. 