.. _modules_courses:

*****************
Courses
*****************

The Courses module provides an interface to the university-wide course catalog for Harvard University. It provides the following high-level functionality:

    * Browse classes by School and Departments/Programs
    * View individual class details
    * Search for classes (in all schools; specific schools; specific department/programs)


=================================
Schools and Department/Programs
=================================

The list of schools and departments/programs within each school is requested by the iPhone app as soon as the application is launched (and if the schools are not cached on the iPhone itself):

    http://m.harvard.edu/api/?module=courses&command=courses

This server call then requests the information from the courses data-feed:
    http://services.isites.harvard.edu/course_catalog/api/v1/search?

The *facets* at the bottom of this feed contain the list of all **school names** (*school_nm*).

Using each of the extracted *school_nm* fields, the list of department/programs in each school is extracted by making the following call to the courses-feed:

    http://services.isites.harvard.edu/course_catalog/api/v1/search?fq_school_nm:school_nm="<school+name>"&

In this query, *<school+name>* should specify the name of the individual schools. Once the query results are retrieved, the departments/programs within each school are populated using the facets at the bottom.
The *dept_area_category* fields contain the department/programs within the specified school.

Once all department/programs are mapped to each school, the mapping is returned as a JSON string:

.. code-block:: javascript

    [
        {"school_name":"Harvard Business School - Doctoral Program","school_name_short":"Business - Doctoral Program","courses":[{"name":"","short":"1"}]},
        {"school_name":"Harvard Business School - MBA Program","school_name_short":"Business - MBA Program","courses":[{"name":"","short":"1"}]},
        {"school_name":"Harvard Extension School","school_name_short":"Continuing Education","courses":[{"name":"Anthropology and Archaeology","short":"1"},{"name":"Arabic","short":"1"},{"name":"Astronomy","short":"1"},{"name":"Biological Sciences","short":"1"},{"name":"Biotechnology","short":"1"},{"name":"Chemistry","short":"1"},{"name":"Chinese","short":"1"},{"name":"Classics","short":"1"},{"name":"Computer Science","short":"1"},{"name":"Creative Writing","short":"1"},{"name":"Dramatic Arts","short":"1"},{"name":"Economics","short":"1"},{"name":"Education","short":"1"},{"name":"Engineering Sciences","short":"1"},{"name":"English","short":"1"},{"name":"Environmental Studies","short":"1"},{"name":"Expository Writing","short":"1"},{"name":"Foreign Literature and Culture","short":"1"},{"name":"French Language and Literature","short":"1"},{"name":"German","short":"1"},{"name":"Government","short":"1"},{"name":"Greek","short":"1"},{"name":"History","short":"1"},{"name":"History of Art and Architecture","short":"1"},{"name":"History of Science","short":"1"},{"name":"Humanities","short":"1"},{"name":"Information Systems Management","short":"1"},{"name":"Italian","short":"1"},{"name":"Japanese","short":"1"},{"name":"Journalism","short":"1"},{"name":"Latin","short":"1"},{"name":"Legal Studies","short":"1"},{"name":"Management","short":"1"},{"name":"Mathematics","short":"1"},{"name":"Museum Studies","short":"1"},{"name":"Music","short":"1"},{"name":"Philosophy","short":"1"},{"name":"Physics","short":"1"},{"name":"Portuguese","short":"1"},{"name":"Psychology","short":"1"},{"name":"Religion","short":"1"},{"name":"Social Sciences","short":"1"},{"name":"Spanish Language and Literature","short":"1"},{"name":"Speech","short":"1"},{"name":"Statistics","short":"1"},{"name":"Studio Arts and Film","short":"1"},{"name":"Study and Research Skills","short":"1"}]},
        {"school_name":"Harvard School of Dental Medicine","school_name_short":"Dental","courses":[{"name":"","short":"1"}]},
        {"school_name":"Harvard Graduate School of Design","school_name_short":"Design","courses":[{"name":"Architecture","short":"1"},{"name":"Landscape Architecture","short":"1"},{"name":"Urban Planning and Design","short":"1"}]},
        {"school_name":"Harvard Divinity School","school_name_short":"Divinity","courses":[{"name":"","short":"1"}]},
        {"school_name":"Harvard Graduate School of Education","school_name_short":"Education","courses":[{"name":"","short":"1"},{"name":"Arts in Education","short":"1"},{"name":"Cognitive Development and Education","short":"1"},{"name":"Culture, Communities, and Contexts","short":"1"},{"name":"Curriculum","short":"1"},{"name":"Development in Specific Age Periods","short":"1"},{"name":"Diversity and Equity","short":"1"},{"name":"Economics of Education","short":"1"},{"name":"Education Policy","short":"1"},{"name":"Higher and Lifelong Learning","short":"1"},{"name":"History, Philosophy, and Foundations of Education","short":"1"},{"name":"International Education","short":"1"},{"name":"Language and Literacy","short":"1"},{"name":"Leadership, Management, and Organizations","short":"1"},{"name":"Research Methods and Data Analysis","short":"1"},{"name":"Risk, Resilience, and Prevention","short":"1"},{"name":"Schools","short":"1"},{"name":"Social Development and Education","short":"1"},{"name":"Sociology of Education","short":"1"},{"name":"Teaching and Supervision","short":"1"},{"name":"Technology","short":"1"}]},
        {"school_name":"Faculty of Arts and Sciences","school_name_short":"Faculty of Arts and Sciences","courses":[{"name":"Aesthetic and Interpretive Understanding","short":"1"},{"name":"African and African American Studies","short":"1"},{"name":"Akkadian","short":"1"},{"name":"American Civilization","short":"1"},{"name":"Ancient Near East","short":"1"},{"name":"Anthropology","short":"1"},{"name":"Applied Mathematics","short":"1"},{"name":"Applied Physics","short":"1"},{"name":"Arabic","short":"1"},{"name":"Aramaic","short":"1"},{"name":"Armenian","short":"1"},{"name":"Armenian Studies","short":"1"},{"name":"Astronomy","short":"1"},{"name":"BBS","short":"1"},{"name":"BCMP","short":"1"},{"name":"BPH","short":"1"},{"name":"Biological Sciences in Dental Medicine","short":"1"},{"name":"Biophysics","short":"1"},{"name":"Biostatistics","short":"1"},{"name":"Catalan","short":"1"},{"name":"Cell Biology","short":"1"},{"name":"Celtic","short":"1"},{"name":"Chemical Biology","short":"1"},{"name":"Chemical and Physical Biology","short":"1"},{"name":"Chemistry","short":"1"},{"name":"Chinese","short":"1"},{"name":"Chinese History","short":"1"},{"name":"Chinese Literature","short":"1"},{"name":"Classical Hebrew","short":"1"},{"name":"Classical Philology","short":"1"},{"name":"Classical Studies","short":"1"},{"name":"Classics","short":"1"},{"name":"Comparative Literature","short":"1"},{"name":"Computer Science","short":"1"},{"name":"Culture and Belief","short":"1"},{"name":"Design","short":"1"},{"name":"Developmental & Regenerative Biology","short":"1"},{"name":"Dramatic Arts","short":"1"},{"name":"Early Iranian Civilizations","short":"1"},{"name":"Earth and Planetary Sciences","short":"1"},{"name":"East Asian Buddhist Studies","short":"1"},{"name":"East Asian Studies","short":"1"},{"name":"Economics","short":"1"},{"name":"Egyptian","short":"1"},{"name":"Empirical and Mathematical Reasoning","short":"1"},{"name":"Engineering Sciences","short":"1"},{"name":"English","short":"1"},{"name":"Environmental Science and Public Policy","short":"1"},{"name":"Ethical Reasoning","short":"1"},{"name":"Expository Writing","short":"1"},{"name":"Folklore and Mythology","short":"1"},{"name":"Foreign Cultures","short":"1"},{"name":"French","short":"1"},{"name":"Freshman Seminar","short":"1"},{"name":"Genetics","short":"1"},{"name":"German","short":"1"},{"name":"Germanic Philology","short":"1"},{"name":"Gikuyu","short":"1"},{"name":"Global Health and Health Policy","short":"1"},{"name":"Government","short":"1"},{"name":"Graduate Audit","short":"1"},{"name":"Graduate Independent Study","short":"1"},{"name":"Graduate Research","short":"1"},{"name":"Graduate Teaching","short":"1"},{"name":"Greek","short":"1"},{"name":"Health Policy","short":"1"},{"name":"Hebrew","short":"1"},{"name":"Historical Study","short":"1"},{"name":"History","short":"1"},{"name":"History and Literature","short":"1"},{"name":"History of Art and Architecture","short":"1"},{"name":"History of Science","short":"1"},{"name":"Human Biology and Translational Medicine","short":"1"},{"name":"Human Evolutionary Biology","short":"1"},{"name":"Immunology","short":"1"},{"name":"Independent Study","short":"1"},{"name":"Indian Studies","short":"1"},{"name":"Iranian","short":"1"},{"name":"Islamic Civilizations","short":"1"},{"name":"Italian","short":"1"},{"name":"Japanese","short":"1"},{"name":"Japanese History","short":"1"},{"name":"Japanese Literature","short":"1"},{"name":"Jewish Studies","short":"1"},{"name":"Korean","short":"1"},{"name":"Korean History","short":"1"},{"name":"Korean Literature","short":"1"},{"name":"Latin","short":"1"},{"name":"Latin American Studies","short":"1"},{"name":"Life Sciences","short":"1"},{"name":"Life and Physical Sciences","short":"1"},{"name":"Linguistics","short":"1"},{"name":"Literature","short":"1"},{"name":"Literature and Arts","short":"1"},{"name":"MCB","short":"1"},{"name":"Manchu","short":"1"},{"name":"Mathematics","short":"1"},{"name":"Medical Sciences","short":"1"},{"name":"Medieval Latin","short":"1"},{"name":"Medieval Studies","short":"1"},{"name":"Microbiology","short":"1"},{"name":"Middle Eastern Studies","short":"1"},{"name":"Mind, Brain, and Behavior","short":"1"},{"name":"Modern Greek","short":"1"},{"name":"Modern Hebrew","short":"1"},{"name":"Mongolian","short":"1"},{"name":"Music","short":"1"},{"name":"Near Eastern Civilizations","short":"1"},{"name":"Nepali","short":"1"},{"name":"Neurobiology","short":"1"},{"name":"OEB","short":"1"},{"name":"Pali","short":"1"},{"name":"Pathology","short":"1"},{"name":"Persian","short":"1"},{"name":"Philosophy","short":"1"},{"name":"Physical Sciences","short":"1"},{"name":"Physics","short":"1"},{"name":"Portuguese","short":"1"},{"name":"Psychology","short":"1"},{"name":"Regional Studies _ East Asia","short":"1"},{"name":"Regional Studies _ Russia, Eastern Europe, and Central Asia","short":"1"},{"name":"Religion","short":"1"},{"name":"Romance Studies","short":"1"},{"name":"SCRB","short":"1"},{"name":"Sanskrit","short":"1"},{"name":"Scandinavian","short":"1"},{"name":"Science of Living Systems","short":"1"},{"name":"Science of the Physical Universe","short":"1"},{"name":"Semitic Philology","short":"1"},{"name":"Slavic","short":"1"},{"name":"Social Analysis","short":"1"},{"name":"Social Policy","short":"1"},{"name":"Social Studies","short":"1"},{"name":"Societies of the World","short":"1"},{"name":"Sociology","short":"1"},{"name":"Spanish","short":"1"},{"name":"Special Concentrations","short":"1"},{"name":"Statistics","short":"1"},{"name":"Studies of Women, Gender, and Sexuality","short":"1"},{"name":"Sumerian","short":"1"},{"name":"Swahili","short":"1"},{"name":"Swedish","short":"1"},{"name":"Systems Biology","short":"1"},{"name":"Tamil","short":"1"},{"name":"Tibetan","short":"1"},{"name":"Turkish","short":"1"},{"name":"Twi","short":"1"},{"name":"Ukrainian","short":"1"},{"name":"United States in the World","short":"1"},{"name":"Urdu","short":"1"},{"name":"Uyghur","short":"1"},{"name":"Vietnamese","short":"1"},{"name":"Virology","short":"1"},{"name":"Visual and Environmental Studies","short":"1"},{"name":"Yiddish","short":"1"},{"name":"Yoruba","short":"1"}]},
        {"school_name":"Harvard Kennedy School","school_name_short":"Government","courses":[{"name":"","short":"1"}]},
        {"school_name":"Harvard Law School","school_name_short":"Law","courses":[{"name":"Law","short":"1"}]},
        {"school_name":"Harvard Medical School","school_name_short":"Medical","courses":[{"name":"","short":"1"},{"name":"Undergrad Medical Education","short":"1"}]},
        {"school_name":"Harvard School of Public Health","school_name_short":"Public Health","courses":[{"name":"","short":"1"},{"name":"Biostatistics","short":"1"},{"name":"Division of Biological Science","short":"1"},{"name":"Environmental Health","short":"1"},{"name":"Epidemiology","short":"1"},{"name":"Global Health & Population","short":"1"},{"name":"Health Policy & Management","short":"1"},{"name":"Immunology Infectious Disease","short":"1"},{"name":"Nutrition","short":"1"},{"name":"Soc, Human Devlp, and Health","short":"1"}]}
    ]

**Note: *courses* refers to departments/programs**

It is possible for some department/program (*course*) names to be empty. This just means that there are classes within this school that have no designated "department/program".


============
Class Lists
============

==============
Class Details
==============

=====
Term
=====

=====================
Search (all schools)
=====================

=============================================
Search (specific school, department/program)
=============================================


===============
Services Used
===============