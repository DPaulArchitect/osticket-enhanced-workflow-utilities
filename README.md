# osTicket Add-On: Enhanced Workflow Utilities

This add-on introduces two new features designed to streamline your osTicket workflow and improve agent efficiency. Installation is straightforward, requiring simple file replacements within your existing osTicket installation.

## Features

**1. Canned Responses for Internal Actions:**

This feature extends the utility of your pre-defined canned/stored messages beyond standard ticket replies. You can now leverage these saved responses when:

* **Adding Internal Notes:** Quickly add common internal communications and updates to tickets.
* **Assigning Tickets:** Provide context and instructions when assigning tickets to other agents or departments.
* **Transferring Tickets:** Include relevant information when transferring tickets to different departments.

This enhancement saves agents time and ensures consistency in internal communication.

**2. "Mark Answered" Filter Action:**

Take control of your incoming ticket flow with the new "Mark Answered" filter action. This powerful feature allows you to create ticket filters that automatically move newly created tickets directly to the "Answered" status based on predefined criteria.

This is particularly useful for:

* **Automating the handling of common or easily resolved inquiries.**
* **Prioritizing urgent or critical tickets by filtering out less immediate requests.**
* **Maintaining a cleaner "Open" queue by automatically categorizing resolved issues.**

## Installation

**Warning:** Before proceeding with the installation, it is **highly recommended to create a full backup of your osTicket installation** (files and database) to prevent data loss in case of any unforeseen issues.

This add-on utilizes a simple file replacement method. Please follow these steps carefully:

1.  **Download the Add-On/MOD package.**
2.  **Extract the contents of the package.** You will find a structure of files and potentially directories mirroring your osTicket installation.
3.  **Carefully navigate to the corresponding directories within your osTicket installation on your server.**
4.  **Replace the existing files with the files provided in the Add-On/MOD package.** Ensure you are replacing the correct files in the correct locations.
5.  **Clear your osTicket cache** (if enabled) to ensure the changes are reflected immediately. This can usually be done via the osTicket admin panel or by manually deleting the contents of the cache directory.

**Important:** Ensure file permissions are correctly set after replacing the files to maintain the security and functionality of your osTicket installation.

## Compatibility

This Add-On / MOD has been thoroughly tested and is compatible with **all versions within the v1.18 series**, including:

* Version 1.18.0
* Version 1.18.1
* Version 1.18.2

It may also be compatible with future minor releases within the v1.18 branch, but this has not been explicitly tested.

## Support

For any questions, issues, or feedback regarding this Add-On / MOD, please [**Insert your preferred contact method here, e.g., contact the developer via [link/email address], open an issue on [repository link]**].

Thank you for using the Enhanced Workflow Utilities for osTicket!
