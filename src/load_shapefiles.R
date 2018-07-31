# Read in shapefile containing administrative boundaries (Census OA and MOSOA) --  made available from UK Data Service: https://census.ukdataservice.ac.uk.
# For convenience, we provide a version with geometries simplified using the "rmapshaper" library.
download.file("http://homepages.see.leeds.ac.uk/~georjb/datasets/maplineups_shapefiles.zip", "maplineups_shapefiles.zip")
unzip("maplineups_shapefiles.zip")
england_OAs <- readOGR(dsn = ".", layer = "england_oa_2011")
england_OAs@data$row <- 1:nrow(england_OAs@data)
proj4string(england_OAs) <- CRS("+init=epsg:27700")
# Middle super output areas (for grouping output areas)
england_msoas <- readOGR(dsn = "shapefiles", layer = "msoa_boundaries")
england_msoas@data$row <- 1:nrow(england_msoas@data)
proj4string(england_msoas) <- CRS("+init=epsg:27700")
colnames(england_msoas@data) <- c("msoa","msoaNm","msoaNm2","row")
# oa to msoa lookup
msoas <- read.csv("http://homepages.see.leeds.ac.uk/~georjb/datasets/oa_msoa.csv", header = TRUE)
msoas <- msoas[,1:4]
msoas$LSOA11CD <- NULL
msoas$LSOA11NM <- NULL
colnames(msoas) <- c("oa","msoa")
# Filter msoas based on msoas for which we have geometries
msoas <- msoas[which(england_OAs@data$CODE %in% msoas$oa ),]
candidate_msoas <- msoas %>% select(msoa) %>% distinct(msoa)
colnames(candidate_msoas) <- c("msoa")
