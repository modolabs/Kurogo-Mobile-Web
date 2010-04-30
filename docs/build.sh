#!/bin/bash

# build the sphinx html
make html

# build the doxygen html
doxygen doxygen.conf
mv doxygen_build/html _build/html/doxygen