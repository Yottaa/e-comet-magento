# Yottaa eComet Plugin for Magento (Beta)

Yottaa Site Optimizer will speed up your Magento website automatically.  Faster sites have lower bounce rate, higher conversion rate, and more sales.

Whether you're already a Yottaa Site Optimizer user or want to try it for the first time, you'll enjoy the ease of having a Yottaa control panel right on your Magento Dashboard. Plugin users also have access to special caching features only available through the Magento eComet plugin, which can improve page speed even beyond Yottaa Site Optimizer's standard techniques.

## Plugin Setup ##

[Setup Guide](http://www.yottaa.com/reference-materials/magento-plugin-instructions/)

## Build Plugin ##

1. Install Ant

    Install and add [required jars](http://ant.apache.org/manual/Tasks/scp.html) for scp task.

2. Build Yottaa Module

    Once you clone the repository, run following ant command to build the Yottaa module

    ```
    ant package
    ```

    You can then install the module with the generated zip file.

3. Setup Dev Environment for Yottaa Module

    If you have local or remote installation of Magento and you want update the module constantly, you can add a custom-build.properties file right under the root directory of your copy of github project.

    Put following configurations in the properties file and replace the values with your own settings

    ```
    magento.location=[Root directory of your local Magento installation]
    scp.magento.host=[Server IP for your remote Magento installation]
    scp.magento.username=[Username for accessing your server]
    scp.magento.password=[Password for accessing your server]
    scp.magento.basepath=[Root directory of your remote Magento installation]
    ```

    You can then run

    ```
    ant dev
    ```
    to update your local Magento installation.

    or

    ```
    ant publish
    ```
    to update your remote Magento installation.

## Links ##

* [Yottaa](http://www.yottaa.com)
* [Magento](http://www.magentocommerce.com/)