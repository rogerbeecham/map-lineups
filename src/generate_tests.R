# -------- required packages ------------

#geog packages
library(rgdal) 
library(rgeos) 
library(spdep)
library(gtools)
library(spatstat) 
library(FNN)
#plotting packages
library(tmap)
library(ggplot2)
library(gridExtra)
#data munging packages
library(magrittr) 
library(dplyr)

# -------- load geographies ------------

england_OAs <- readOGR(dsn = "shapefiles", layer = "england_oa_2011")
england_OAs@data$row <- 1:nrow(england_OAs@data)
proj4string(england_OAs) <- CRS("+init=epsg:27700")
spTransform(england_OAs, CRS("+init=epsg:27700 +units=km"))
#middle super output areas (for grouping output areas)
england_msoas <- readOGR(dsn = "shapefiles", layer = "msoa_boundaries")
england_msoas@data$row <- 1:nrow(england_msoas@data)
proj4string(england_msoas) <- CRS("+init=epsg:27700")
spTransform(england_msoas, CRS("+init=epsg:27700 +units=km"))
colnames(england_msoas@data) <- c("msoa","msoaNm","msoaNm2","row")
#oa to msoa lookup
msoas <- read.csv("shapefiles/oa_msoa.csv", header = TRUE)
msoas <- msoas[,1:4]
msoas$LSOA11CD<- NULL
msoas$LSOA11NM<- NULL
colnames(msoas) <- c("oa","msoa")
#filter msoas based on msoas for which we have geometries
msoas <-  msoas[which( england_OAs@data$CODE %in% msoas$oa ),]
candidate_msoas <- msoas %>% select(msoa) %>% distinct(msoa)
colnames(candidate_msoas) <- c("msoa")


# -------- required functions ------------

#function for finding a region of a given irregularity (as measured by coefficient of variaiton in area)
find.geography <- function(england_msoas, msoas, england_OAs, coefVarMin, coefVarMax) 
{
  repeat
  {
    sample_msoa <- england_msoas[sample(england_msoas@data$row, 1),]
    sample_msoas <- england_msoas@data[get.knnx(coordinates(england_msoas),coordinates(sample_msoa), k=2)$nn.index,]
    sample_OAs<- msoas[which( msoas$msoa %in% sample_msoas$msoa ),]
    if(nrow(sample_OAs)>0)
    {
      
        sample_geoms <- england_OAs[england_OAs@data$CODE %in%  sample_OAs$oa,]
        centroids<- gCentroid(sample_geoms, byid=TRUE)
        coefVar<- sd(gArea(sample_geoms, byid=TRUE)/1000/1000)/mean(gArea(sample_geoms, byid=TRUE)/1000/1000)
       
          if(coefVar>coefVarMin && coefVar<=coefVarMax && length(sample_geoms) > 44 && length(sample_geoms) < 56 )
          {
            sample_geoms@data$row<-1:length(sample_geoms)
            return(sample_geoms)
            break
          }
    }
  }
}

# function for appending attribute data
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

generate.dirs.geography<- function(mainDir, subDir, geogDir, targetDirs)
{
  dir.create(file.path(mainDir,subDir, geogDir), showWarnings = FALSE)
  for(i in 1:length(targetDirs))
  {
    dir.create(file.path(mainDir,subDir,geogDir,targetDirs[i]), showWarnings = FALSE)
    dir.create(file.path(mainDir,subDir,geogDir,targetDirs[i],"above"), showWarnings = FALSE)
    dir.create(file.path(mainDir,subDir,geogDir,targetDirs[i],"below"), showWarnings = FALSE)
  }
}

generate.stimulus.geography <- function(mainDir, subDir, geogDir, targetDirs, targets, region)
{
  for(i in 1:length(targets))
  {
    target<-targets[i]
    targetDir<-targetDirs[i]
    approach<-"below"
    start<-0
    numSteps<- target*20+2
    steps <- data.frame(matrix(ncol= 3, nrow = numSteps))
    colnames(steps) <- c("min","max","name")
    for(j in 1:numSteps)
    {
      steps[j,1]<-start
      steps[j,2]<-start+0.01
      steps[j,3]<- paste("step_", round(abs(target-start),2), sep="")
      if(j<numSteps-2)
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
    dir.create(file.path(mainDir,subDir,geogDir,targetDir,approach), showWarnings = FALSE)
    generate.stimulus.target(region, target, target+0.01, steps, mainDir, subDir, geogDir, targetDir, approach)
    
    if(target!=0.9)
    {
      
      approach<-"above"
      start<-0.9
      numSteps<- (0.9-target)*20+2
      steps <- data.frame(matrix(ncol= 3, nrow = numSteps))
      colnames(steps) <- c("min","max","name")
      condition<- numSteps-2
      print(condition)
      
      for(k in 1:numSteps)
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
      dir.create(file.path(mainDir,subDir,geogDir,targetDir,approach), showWarnings = FALSE)
      generate.stimulus.target(region, target, target+0.01, steps, mainDir, subDir, geogDir, targetDir, approach)
    }
  }
}

generate.stimulus.target <- function(region, targetMin, targetMax, steps, mainDir, subDir, geogDir, targetDir, approach)
{
  for(i in 1:nrow(steps))
  {
    dir.create(file.path(mainDir,subDir,geogDir,targetDir,approach,steps[i,3]), showWarnings = FALSE)
    for(j in 0:6)
    {
      path1TextC<-NULL
      path1MapC<- NULL
      path2TextC<-NULL
      path2MapC<- NULL
      iteration1<- paste("iteration_",(j*2)+1,sep="")
      dir.create(file.path(mainDir,subDir,geogDir,targetDir,approach,steps[i,3],iteration1), showWarnings = FALSE)
      path1TextC <- file.path(paste(mainDir,"/",subDir,"/",geogDir,"/",targetDir,"/",approach,"/",steps[i,3],"/",iteration1,"/comparator.txt", sep = ""))
      path1MapC <- file.path(paste(mainDir,"/",subDir,"/",geogDir,"/",targetDir,"/",approach,"/",steps[i,3],"/",iteration1,"/comparator.png", sep = ""))
      if(j<6)
      {
        iteration2<- paste("iteration_",(j*2)+2,sep="")
        dir.create(file.path(mainDir,subDir,geogDir,targetDir,approach,steps[i,3],iteration2), showWarnings = FALSE)
        path2TextC <- file.path(paste(mainDir,"/",subDir,"/",geogDir,"/",targetDir,"/",approach,"/",steps[i,3],"/",iteration2,"/comparator.txt", sep = ""))
        path2MapC <- file.path(paste(mainDir,"/",subDir,"/",geogDir,"/",targetDir,"/",approach,"/",steps[i,3],"/",iteration2,"/comparator.png", sep = ""))
      }
      
      comparator<- generate.map(region, steps[i,1], steps[i,2],path1TextC, path1MapC, path2TextC, path2MapC)
      path1TextT<-NULL
      path1MapT<- NULL
      path2TextT<-NULL
      path2MapT<- NULL
      
      path1TextT <- file.path(paste(mainDir,"/",subDir,"/",geogDir,"/",targetDir,"/",approach,"/",steps[i,3],"/",iteration1,"/target.txt", sep = ""))
      path1MapT <- file.path(paste(mainDir,"/",subDir,"/",geogDir,"/",targetDir,"/",approach,"/",steps[i,3],"/",iteration1,"/target.png", sep = ""))
      if(j<6)
      {
        iteration2<- paste("iteration_",(j*2)+2,sep="")
        path2TextT <- file.path(paste(mainDir,"/",subDir,"/",geogDir,"/",targetDir,"/",approach,"/",steps[i,3],"/",iteration2,"/target.txt", sep = ""))
        path2MapT <- file.path(paste(mainDir,"/",subDir,"/",geogDir,"/",targetDir,"/",approach,"/",steps[i,3],"/",iteration2,"/target.png", sep = ""))
      }
      target<- generate.map(region, targetMin, targetMax,path1TextT, path1MapT, path2TextT, path2MapT)
    }
  }
}

generate.map <- function(data, min, max, path1Text, path1Map, path2Text, path2Map) 
{
  data.nb <- poly2nb(data)
  data.dsts <- nbdists(data.nb, coordinates(data))
  idw <- lapply(data.dsts, function(x) 1/x)
  data.lw <- nb2listw(data.nb, glist = idw)
  
  repeat
  {
    #start with a new permutation
    permutation<- data@data[sample(nrow(data@data)),]
    iNow <- moran.test(permutation$value, data.lw)$estimate[1]
    iOld<- iNow
    dOld<-NULL
    dOld=sqrt((iNow-max)^2)
    
    #if after 5000 attempts, still not reached desired Moran's I, then try a new permutation.
    for(j in 1:5000)
    {
      #break if reached desired I.
      if(iNow >=min && iNow < max)
      {
        break
      }  
      
      #sample two distinct positions  
      swapIndex <- sample(1:nrow(permutation),2, replace=FALSE)
      #get values corresponding to these positions
      swapValues <- sapply(swapIndex,function(rowIndex){return(permutation$value[rowIndex])})
      
      #swap the values 
      permutation$value[swapIndex[1]]<-swapValues[2]
      permutation$value[swapIndex[2]]<-swapValues[1]
      
      #calculate new Moran's I  
      iNow <- moran.test(permutation$value, data.lw)$estimate[1]
      dNow=sqrt((iNow-max)^2)
      
      #revert back if it's not reduced distance to targets 
      if(dNow<dOld)
      {
        iOld <- iNow
        dOld <- dNow
      }
      else #revert if no nearer target
      {  
        iNow<-iOld
        permutation$value[swapIndex[1]]<-swapValues[1]
        permutation$value[swapIndex[2]]<-swapValues[2]
      }
    }
    #break if reached desired I.
    if(iNow > min && iNow <=max)
    {
      break
    }  
  }
  
  data@data <- cbind(data@data,permutation$value);
  colnames(data@data)[4] <- "permutation"
  
  minValue<-min(data@data$permutation)
  maxValue<-max(data@data$permutation)
  weightedVal = 0
  numAreas<-nrow(data@data)
  for(i in 1: numAreas)
  {
    iarea<- gArea(data[i,], byid=TRUE)/1000/1000
    idata <- data@data$permutation[i]
    weightedVal<- weightedVal+(idata*iarea)
  }
  
  #average attribute value per unit of area
  area<-gArea(data, byid=FALSE)/1000/1000
  weightedVal<-weightedVal/area
  
  idwSquared <- lapply(data.dsts, function(x) 1/(x^2))
  data.lwSquared <- nb2listw(data.nb, glist = idwSquared)
  data.lwNon <- nb2listw(data.nb)
  
  iNowSquared <- moran.test(permutation$value, data.lwSquared)$estimate[1]
  iNowNon <- moran.test(permutation$value, data.lwNon)$estimate[1]
  
  write(c(iNow,iNowSquared, iNowNon,weightedVal),sep = ",",file=path1Text)
  
  #draw maps
  map1<- tm_shape(data) +
    tm_fill(c("permutation"),style="cont", palette="YlOrBr")+
    tm_borders(col="gray80", lwd=2)+
    tm_layout(legend.show=FALSE,frame=FALSE)
  
  aspectRatio<- (max(coordinates(data)[,2])-min(coordinates(data)[,2]))/(max(coordinates(data)[,1])-min(coordinates(data)[,1]))
  png(filename=path1Map, width=800, height=800*aspectRatio)
  print(map1)
  dev.off()
  
  if(is.null(path2Text))
  {
    
  }
  else
  {
    weightedVal = 0
    for(i in 1: numAreas)
    {
      iarea<- gArea(data[i,], byid=TRUE)/1000/1000
      idata <- map(data@data$permutation[i], minValue, maxValue,maxValue,minValue)
      weightedVal <- weightedVal+ (idata*iarea)
    }
    weightedVal <- weightedVal/area
  
    write(c(iNow,iNowSquared,iNowNon,weightedVal),sep = ",",file=path2Text)
    
    map2<- tm_shape(data) +
      tm_fill(c("permutation"),style="cont", palette="-YlOrBr")+
      tm_borders(col="gray80", lwd=2)+
      tm_layout(legend.show=FALSE,frame=FALSE)
    
    png(filename=path2Map, width=800, height=800*aspectRatio)
    print(map2)
    dev.off()
  }  
}


map <- function(value, min1, max1, min2, max2)
{
  return  (min2+(max2-min2)*((value-min1)/(max1-min1)))
}




# ----------- generate maps for a given geography ------

# setup folder structure
mainDir<- getwd()
subDir<-"tests"
dir.create(file.path(mainDir,subDir), showWarnings = FALSE)
targetDirs<- c("moran_0.9","moran_0.8","moran_0.7","moran_0.6","moran_0.5","moran_0.4","moran_0.3","moran_0.2")
targets<-c(0.9, 0.8,0.7,0.6,0.5,0.4,0.3,0.2)

# find a geography 
geog1 <- find.geography(england_msoas, msoas, england_OAs, 0, 0.4)
plot(geog1)
geog1<- create.attribute.data(geog1)
region<- geog1

# Generate maps in folder structure used by survey software. Additionally save in text files, contextual data used for exploratpry analysis: coloir value per unit area, actual Moran's I, using different weighting schemes.
geogDir<-"geography_1"
generate.dirs.geography(mainDir, subDir, geogDir, targetDirs)
generate.stimulus.geography(mainDir, subDir, geogDir, targetDirs, targets, region)











