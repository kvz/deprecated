#// LANG::Bash
#// Sample starts here
# Import from SVN
cd ${HOME}/workspace
git svn clone --authors-file=${HOME}/.authors svn://svn.example.com/projectX/trunk projectX

cd projectX

# Rewrite history
git filter-branch --tree-filter 'git ls-files -z "*.php" |xargs -0 perl -p -i -e "s#(PASSWORD1|PASSWORD2|PASSWORD3)#xXxXxXxXxXx#g"' -- --all

# Make workspace look like HEAD
git reset --hard

# Try to recompress and clean up, then check the new size
git gc --aggressive --prune

# To GitHub
git remote add origin git@github.com:kvz/projectX.git
git push origin master
