WP Simple media locations management with real folders
============================


Plugin by mheadley: https://mheadley.com

Visit Plugin site:https://mheadley.com/developing/wordpress-plugins/wp-simple-media-locations-management-with-real-folders/

==Instructions==
1) Upload to wp-content/uploads/plugins folder
2) got to plugins -> add new  to locate plugin and activate

==using plugin==
Media locations are created under media - > media locations menu item.  
slug is used to create folder, name is used as frontend display only. 

There are few allowed characters in slugs only numbers, letters and hyphens.  
*(underscores will save to db but not save to folder name)*
if you want to have more than one location with the same slug please use an "_".  

using an "_" in a location slug will cause all text to be parsed and ONLY the last portion will be used. 
examples: "mike_h_folder" -> "folder", "1_nooo" -> "nooo", "black" -> "black".