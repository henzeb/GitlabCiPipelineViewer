# GitlabPipelineViewer
With this tool it's possible to review your pipelines before you commit and push. 

The problem with the current implementation of gitlab CI/CD is that it's only possible to see your pipelines when you actually push your new configuration, causing them to be executed as well. Ofcourse, you could use a feature-branch or an entire different repository to test, but it won't be the same. 

The idea is that this tool will become very feature-rich, so that every aspect of your .gitlab-ci.yml can be reviewed.
