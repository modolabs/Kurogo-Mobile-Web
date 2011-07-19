#################
GitHub Repository
#################

Kurogo is an open source project. You are free to download, update and use the code for your own
use without charge. The project uses the `Git <http://git-scm.com/>`_ distributed version control
system. The Git repository is hosted by GitHub. It can be found at https://github.com/modolabs/Kurogo-Mobile-Web.

For those not familiar with Git or GitHub, please view the `GitHub Help Site <http://help.github.com/>`_.

====================================
Forking and Managing your repository
====================================

If you simply want to download the code, you should clone the repository using
:kbd:`git clone git://github.com/modolabs/Kurogo-Mobile-Web.git`

If you are interested in maintaining your own project you should `fork <http://help.github.com/forking/>`_
the project. 

#. Log into GitHub
#. Browse to https://github.com/modolabs/Kurogo-Mobile-Web
#. Click the **fork** icon in the upper right portion of the page
#. (Optional) You may wish to rename your project
#. Clone your project to your local machine. 
#. Set up an upstream remote: 
   
   * :kbd:`git remote add upstream git://github.com/modolabs/Kurogo-Mobile-Web.git`
   * :kbd:`git fetch upstream`
   * :kbd:`git checkout -b upstream upstream/master`

#. When new changes come down you can run:
   
   * :kbd:`git checkout upstream` Change to upstream branch
   * :kbd:`git pull` Pull down changes
   * :kbd:`git checkout master` Change to master branch
   * :kbd:`git merge upstream` Merge changes into master branch

There are certainly other ways to manage your repository, but this method provides flexibility and
will allow you to maintain a branch that represents the current development in the project.

==================
Creating your site 
==================

Because your own project will contain elements that are not part of the master project (i.e. your
own site's images and css assets), we recommend you keep a separate **upstream** branch. This branch
will remain clean and can be merged into your master branch. By creating an upstream branch it also
allows you to more cleanly handle submitting changes back to the project.

From your master branch, make a copy of the *site/Universitas* folder. This is the template site. You
should rename this to match a concise name for your site. Most, if not all, of your coding will take
place in this folder. You can read more about :doc:`creating additional modules <modulenew>`, 
:doc:`extending existing modules <moduleextend>` and :doc:`theming your site <themes>`. Unless you 
have unique needs, you should not need to edit any files outside of your site's directory.

.. _github-submit:

=======================
Submitting your changes
=======================

If you have fixed a bug in the project or would have a new feature to share, you can submit a 
`pull request <http://help.github.com/pull-requests/>`_. This informs the project maintainers that
you have code you wish to be part of the mainline project.

It is **strongly** recommended that you initiate pull requests in the following manner:

#. Make sure your upstream branch is up to date
#. Make a new branch that implements the fixes/features. 
#. Browse to your GitHub project in your web browser
#. Switch to the branch with your fix/feature
#. Click the **pull request** icon
#. Include a description regarding the nature of your work. If there is not sufficient detail, then
   your request may not be accepted. 
#. If you do not initiate your pull request from a separate branch you will likely have to click the
   **change commits** button and select the various commits that include your fix. 
#. Click the send pull request when the changes are appropriate. 

By utilizing this method, you can insure that only the changes appropriate for the project are included in 
your request. It also allows for alterations to be included without affecting your main branch of work.
Sometimes it can take discussion to resolve any issues regarding coding style, questions regarding your
patch and then final integration.
