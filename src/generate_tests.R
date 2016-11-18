# -------- required packages ------------

# Geog packages
library(rgdal) 
library(rgeos) 
library(spdep)
library(gtools)
library(spatstat) 
library(FNN)
# Plotting packages
library(tmap)
library(ggplot2)
library(gridExtra)
# Data munging packages
library(magrittr) 
library(dplyr)

# -------- load geographies ------------

england_OAs <- readOGR(dsn = "shapefiles", layer = "england_oa_2011")
england_OAs@data$row <- 1:nrow(england_OAs@data)
proj4string(england_OAs) <- CRS("+init=epsg:27700")
# msoas (for grouping output areas)
england_msoas <- readOGR(dsn = "shapefiles", layer = "msoa_boundaries")
england_msoas@data$row <- 1:nrow(england_msoas@data)
proj4string(england_msoas) <- CRS("+init=epsg:27700")
colnames(england_msoas@data) <- c("msoa","msoaNm","msoaNm2","row")
# oa to msoa lookup
msoas <- read.csv("shapefiles/oa_msoa.csv", header = TRUE)
msoas <- msoas[,1:4]
msoas$LSOA11CD<- NULL
msoas$LSOA11NM<- NULL
colnames(msoas) <- c("oa","msoa")
# Filter msoas based on msoas for which we have geometries
msoas <-  msoas[which( england_OAs@data$CODE %in% msoas$oa ),]
candidate_msoas <- msoas %>% select(msoa) %>% distinct(msoa)
colnames(candidate_msoas) <- c("msoa")


# -------- required functions ------------

# Function for finding a region of a given irregularity (as measured by coefficient of variaiton in area)
find.geography <- function(england_msoas, msoas, england_OAs, coef_var_min, coef_var_max) 
{
  repeat
  {
    sample_msoa <- england_msoas[sample(england_msoas@data$row, 1),]
    sample_msoas <- england_msoas@data[get.knnx(coordinates(england_msoas),coordinates(sample_msoa), k=2)$nn.index,]
    sample_OAs<- msoas[which(msoas$msoa %in% sample_msoas$msoa),]
    if(nrow(sample_OAs)>0)
    {
      
        sample_geoms <- england_OAs[england_OAs@data$CODE %in%  sample_OAs$oa,]
        centroids<- gCentroid(sample_geoms, byid=TRUE)
        coefVar<- sd(gArea(sample_geoms, byid=TRUE)/1000/1000)/mean(gArea(sample_geoms, byid=TRUE)/1000/1000)
       
          if(coefVar>coef_var_min && coefVar<=coef_var_max && length(sample_geoms) > 44 && length(sample_geoms) < 56 )
          {
            sample_geoms@data$row<-1:length(sample_geoms)
            return(sample_geoms)
            break
          }
    }
  }
}

# Function for appending attribute data
create.attribute.data <- function(data)
{
  data@data$value<- NULL
  nrows <- nrow(data@data)
  temp<-  data.frame(matrix(ncol = 2, nrow = nrows))
  temp[,1]<- 1:nrows
  dist<-runif(nrows, min=1, max=10)
  temp[,2]<- dist
  colnames(temp)<- c("index","value")
  temp[,1]<- data@data$CODE
  colnames(temp)<- c("CODE","value")
  data@data<-merge(data@data, temp, by="CODE")
  return(data)
}

generate.dirs.geography<- function(main_dir, sub_dir, geog_dir, target_dirs)
{
  dir.create(file.path(main_dir,sub_dir, geog_dir), show_warnings = FALSE)
  for(i in 1:length(target_dirs))
  {
    dir.create(file.path(main_dir,sub_dir,geog_dir,target_dirs[i]), show_warnings = FALSE)
    dir.create(file.path(main_dir,sub_dir,geog_dir,target_dirs[i],"above"), show_warnings = FALSE)
    dir.create(file.path(main_dir,sub_dir,geog_dir,target_dirs[i],"below"), show_warnings = FALSE)
  }
}

generate.stimulus.geography <- function(main_dir, sub_dir, geog_dir, target_dirs, targets, region)
{
  for(i in 1:length(targets))
  {
    target<-targets[i]
    target_dir<-target_dirs[i]
    approach<-"below"
    start<-0
    num_steps<- target*20+2
    steps <- data.frame(matrix(ncol= 3, nrow = num_steps))
    colnames(steps) <- c("min","max","name")
    for(j in 1:num_steps)
    {
      steps[j,1]<-start
      steps[j,2]<-start+0.01
      steps[j,3]<- paste("step_", round(abs(target-start),2), sep="")
      if(j<num_steps-2)
      {
        start<-start+0.05
      }
      else
      {
        start<-start+0.02 
      }
    }
    print("below")
    print(steps)
    dir.create(file.path(main_dir,sub_dir,geog_dir,target_dir,approach), show_warnings = FALSE)
    generate.stimulus.target(region, target, target+0.01, steps, main_dir, sub_dir, geog_dir, target_dir, approach)
    
    if(target!=0.9)
    {
      
      approach<-"above"
      start<-0.9
      num_steps<- (0.9-target)*20+2
      steps <- data.frame(matrix(ncol= 3, nrow = num_steps))
      colnames(steps) <- c("min","max","name")
      condition<- num_steps-2
      print(condition)
      
      for(k in 1:num_steps)
      {
        steps[k,1]<-start
        if(k==1)
        {
          steps[k,2]<-1
        }
        else
        {
          steps[k,2]<-start+0.01
        }
        
        if(target==0.3)
        {
          condition=12
        }
        if(target==0.7)
        {
          condition=4
        }
        
        steps[k,3]<- paste("step_", round(abs(target-start),2), sep="")
        if(k<condition)
        {
          start<-start-0.05
        }
        else
        {
          start<-start-0.02
        }
      }
      print("above")
      print(steps)
      dir.create(file.path(main_dir,sub_dir,geog_dir,target_dir,approach), show_warnings = FALSE)
      generate.stimulus.target(region, target, target+0.01, steps, main_dir, sub_dir, geog_dir, target_dir, approach)
    }
  }
}

generate.stimulus.target <- function(region, target_min, target_max, steps, main_dir, sub_dir, geog_dir, target_dir, approach)
{
  for(i in 1:nrow(steps))
  {
    dir.create(file.path(main_dir,sub_dir,geog_dir,target_dir,approach,steps[i,3]), show_warnings = FALSE)
    for(j in 0:6)
    {
      path1_text_c<-NULL
      path1_map_c<- NULL
      path2_text_c<-NULL
      path2_map_c<- NULL
      iteration1<- paste("iteration_",(j*2)+1,sep="")
      dir.create(file.path(main_dir,sub_dir,geog_dir,target_dir,approach,steps[i,3],iteration1), show_warnings = FALSE)
      path1_text_c <- file.path(paste(main_dir,"/",sub_dir,"/",geog_dir,"/",target_dir,"/",approach,"/",steps[i,3],"/",iteration1,"/comparator.txt", sep = ""))
      path1_map_c <- file.path(paste(main_dir,"/",sub_dir,"/",geog_dir,"/",target_dir,"/",approach,"/",steps[i,3],"/",iteration1,"/comparator.png", sep = ""))
      if(j<6)
      {
        iteration2<- paste("iteration_",(j*2)+2,sep="")
        dir.create(file.path(main_dir,sub_dir,geog_dir,target_dir,approach,steps[i,3],iteration2), show_warnings = FALSE)
        path2_text_c <- file.path(paste(main_dir,"/",sub_dir,"/",geog_dir,"/",target_dir,"/",approach,"/",steps[i,3],"/",iteration2,"/comparator.txt", sep = ""))
        path2_map_c <- file.path(paste(main_dir,"/",sub_dir,"/",geog_dir,"/",target_dir,"/",approach,"/",steps[i,3],"/",iteration2,"/comparator.png", sep = ""))
      }
      
      comparator <- generate.map(region, steps[i,1], steps[i,2],path1_text_c, path1_map_c, path2_text_c, path2_map_c)
      path1_text_t <-NULL
      path1_map_t <- NULL
      path2_text_t <-NULL
      path2_map_t <- NULL
      
      path1_text_t <- file.path(paste(main_dir,"/",sub_dir,"/",geog_dir,"/",target_dir,"/",approach,"/",steps[i,3],"/",iteration1,"/target.txt", sep = ""))
      path1_map_t <- file.path(paste(main_dir,"/",sub_dir,"/",geog_dir,"/",target_dir,"/",approach,"/",steps[i,3],"/",iteration1,"/target.png", sep = ""))
      if(j<6)
      {
        iteration2<- paste("iteration_",(j*2)+2,sep="")
        path2_text_t <- file.path(paste(main_dir,"/",sub_dir,"/",geog_dir,"/",target_dir,"/",approach,"/",steps[i,3],"/",iteration2,"/target.txt", sep = ""))
        path2_map_t <- file.path(paste(main_dir,"/",sub_dir,"/",geog_dir,"/",target_dir,"/",approach,"/",steps[i,3],"/",iteration2,"/target.png", sep = ""))
      }
      target<- generate.map(region, target_min, target_max,path1_text_t, path1_map_t, path2_text_t, path2_map_t)
    }
  }
}

generate.map <- function(data, min, max, path1_text, path1_map, path2_text, path2_map) 
{
  data.nb <- poly2nb(data)
  data.dsts <- nbdists(data.nb, coordinates(data))
  idw <- lapply(data.dsts, function(x) 1/x)
  data.lw <- nb2listw(data.nb, glist = idw)
  
  repeat
  {
    # Start with a new permutation
    permutation<- data@data[sample(nrow(data@data)),]
    i_now <- moran.test(permutation$value, data.lw)$estimate[1]
    i_old <- i_now
    d_old <-NULL
    d_old=sqrt((i_now-max)^2)
    
    # If after 5000 attempts still not reached desired Moran's I, then try a new permutation.
    for(j in 1:5000)
    {
      # Break if reached desired I.
      if(i_now >=min && i_now < max)
      {
        break
      }  
      
      # Sample two distinct positions  
      swap_index <- sample(1:nrow(permutation),2, replace=FALSE)
      # Get values corresponding to these positions
      swap_values <- sapply(swap_index,function(row_index){return(permutation$value[row_index])})
      
      # Swap the values 
      permutation$value[swap_index[1]]<-swap_values[2]
      permutation$value[swap_index[2]]<-swap_values[1]
      
      # Calculate new Moran's I  
      i_now <- moran.test(permutation$value, data.lw)$estimate[1]
      dNow=sqrt((i_now-max)^2)
      
      # Revert back if not reduced distance to targets 
      if(dNow<d_old)
      {
        i_old <- i_now
        d_old <- dNow
      }
      else 
      {  
        i_now<-i_old
        permutation$value[swap_index[1]] <- swap_values[1]
        permutation$value[swap_index[2]] <- swap_values[2]
      }
    }
    # Break if reached desired I.
    if(i_now>min && i_now<=max)
    {
      break
    }  
  }
  
  data@data <- cbind(data@data,permutation$value);
  colnames(data@data)[4] <- "permutation"
  
  min_value <- min(data@data$permutation)
  max_value <- max(data@data$permutation)
  weighted_val = 0
  num_areas <- nrow(data@data)
  for(i in 1: num_areas)
  {
    iarea <- gArea(data[i,], byid=TRUE)/1000/1000
    idata <- data@data$permutation[i]
    weighted_val <- weighted_val+(idata*iarea)
  }
  
  # Average attribute value per unit of area
  area<-gArea(data, byid=FALSE)/1000/1000
  weighted_val<-weighted_val/area
  
  idw_squared <- lapply(data.dsts, function(x) 1/(x^2))
  data_lw_squared <- nb2listw(data.nb, glist = idw_squared)
  data_lw_non <- nb2listw(data.nb)
  
  i_now_squared <- moran.test(permutation$value, data_lw_squared)$estimate[1]
  i_now_non <- moran.test(permutation$value, data_lw_non)$estimate[1]
  
  write(c(i_now,i_now_squared, i_now_non,weighted_val),sep = ",",file=path1_text)
  
  # Draw maps
  map1 <- tm_shape(data) +
    tm_fill(c("permutation"), style="cont", palette="YlOrBr")+
    tm_borders(col="gray80", lwd=2)+
    tm_layout(legend.show=FALSE, frame=FALSE)
  
  aspect_ratio <- (max(coordinates(data)[,2])-min(coordinates(data)[,2]))/(max(coordinates(data)[,1])-min(coordinates(data)[,1]))
  png(filename=path1_map, width=800, height=800*aspect_ratio)
  print(map1)
  dev.off()
  
  if(is.null(path2_text))
  {
    
  }
  else
  {
    weighted_val = 0
    for(i in 1: num_areas)
    {
      iarea <- gArea(data[i,], byid=TRUE)/1000/1000
      idata <- map(data@data$permutation[i], min_value, max_value, max_value, min_value)
      weighted_val <- weighted_val + (idata*iarea)
    }
    weighted_val <- weighted_val/area
  
    write(c(i_now, i_now_squared, i_now_non, weighted_val), sep=",", file=path2_text)
    
    map2 <- tm_shape(data) +
      tm_fill(c("permutation"),style="cont", palette="-YlOrBr")+
      tm_borders(col="gray80", lwd=2)+
      tm_layout(legend.show=FALSE,frame=FALSE)
    
    png(filename=path2_map, width=800, height=800*aspect_ratio)
    print(map2)
    dev.off()
  }  
}


map <- function(value, min1, max1, min2, max2)
{
  return  (min2+(max2-min2)*((value-min1)/(max1-min1)))
}




# ----------- generate maps for a given geography ------

# Setup folder structure
main_dir <- getwd()
sub_dir <-"tests"
dir.create(file.path(main_dir,sub_dir), show_warnings = FALSE)
target_dirs <- c("moran_0.9","moran_0.8","moran_0.7","moran_0.6","moran_0.5","moran_0.4","moran_0.3","moran_0.2")
targets <-c(0.9, 0.8,0.7,0.6,0.5,0.4,0.3,0.2)

# Find a geography 
geog1 <- find.geography(england_msoas, msoas, england_OAs, 0, 0.4)
plot(geog1)
geog1 <- create.attribute.data(geog1)
region <- geog1

# Generate maps in folder structure used by survey software. Additionally save in text files contextual data used for exploratory analysis: colour value per unit area, actual Moran's I, using different weighting schemes.
geog_dir <-"geography_1"
generate.dirs.geography(main_dir, sub_dir, geog_dir, target_dirs)
generate.stimulus.geography(main_dir, sub_dir, geog_dir, target_dirs, targets, region)











