#!/bin/bash
cd "$( dirname "${BASH_SOURCE[0]}" )"
git pull

CommitVer=`git log -1 --format="%H"`
CommitMsg=`git log -n 1 --pretty=format:'%s'`

if [ $? -ne 0 ];
then
  echo ""
  echo "********* ERROR: Could not pull from repo !!! **************"
  echo ""
  exit 1;
fi

# run any script here needed

echo "Project Release complete, git commit: $CommitVer, $CommitMsg"

