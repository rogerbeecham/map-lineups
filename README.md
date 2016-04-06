
# Supplementary details for: _Map line-ups: using graphical inference to study spatial structure_

_Roger Beecham_ <br>
_Jason Dykes_ <br>
_Wouter Meulemans_ <br>
_Aidan Slingsby_ <br>
_Cagatay Turkay_ <br>
_Jo Wood_ <br>

This document contains supplementary information for our paper: _Map line-ups: using graphical inference to study
  spatial structure_.  It outlines the procedure for the experiment (on which the paper is based), code that can be used to run the experiment locally and code used for the data analysis. Note that this draws heavily on the work already published in [Harrison _et al._](https://github.com/TuftsVALT/ranking-correlation) and  [Kay & Heer](https://github.com/mjskay/ranking-correlation).


# Experiment

Below is some code and discussion of how we generate the stimulus used in the eperiment. Code for the experiment itself can be found in the "experiment" folder and instructions are in the README of that folder.  

All stimuli used in the test were created using R.  

## Configure R

The following libraries are required for generating the maps used as stimuli.

```r
# for spatial data handling
library(rgdal)
library(rgeos)
library(spdep)
library(gtools)
library(spatstat)
library(FNN)
# for charting
library(tmap)
library(ggplot2)
library(gridExtra)
# for data munging
library(magrittr)
library(dplyr)
```

## Load shapefiles

```r
england_OAs <- readOGR(dsn = "shapefiles", layer = "england_oa_2011")
england_OAs@data$row <- 1:nrow(england_OAs@data)
proj4string(england_OAs) <- CRS("+init=epsg:27700")
spTransform(england_OAs, CRS("+init=epsg:27700 +units=km"))
england_msoas <- readOGR(dsn = "shapefiles", layer = "msoa_boundaries") # middle super output areas (for grouping output areas)
england_msoas@data$row <- 1:nrow(england_msoas@data)
proj4string(england_msoas) <- CRS("+init=epsg:27700")
spTransform(england_msoas, CRS("+init=epsg:27700 +units=km"))
colnames(england_msoas@data) <- c("msoa","msoaNm","msoaNm2","row")
msoas <- read.csv("shapefiles/oa_msoa.csv", header = TRUE) # oa to msoa lookup
msoas <- msoas[,1:4]
msoas$LSOA11CD<- NULL
msoas$LSOA11NM<- NULL
colnames(msoas) <- c("oa","msoa")
msoas <-  msoas[which( england_OAs@data$CODE %in% msoas$oa ),] # filter msoas based on msoas for which we have geometries
candidate_msoas <- msoas %>% select(msoa) %>% distinct(msoa)
colnames(candidate_msoas) <- c("msoa")
```


## Generating _real_ study regions

Motivating the study is the need to evaluate the line-up protocol when applied to choropleth maps. We want to come up with recommendations for constructing line-up tests in _real_ data analysis scenarios. So we want ecological validity in the stimuli we create. Because of this, we believe there's a strong argument for using real geographic regions as well as testing against more contrived situations (e.g. regular grids).

Below are two approaches to generating these _real_ regions using English Output Areas (OAs):

1. Load a SpatialDataFrame containing all OAs, sample an OA, then find its 50 nearest neighbours.
2. Load a spatial data frame containing all Middle Super Output Areas MSOAs. MSOAs contain on avergae 25 OAs. Sample an MSOA and find its nearest neighbour -- thus we end up with c. 50 geog units, but their grouping is more _real_, since MSOAs are a genuine administrative geography.

Next, we need to decide on different geometries of these regions to use in the testing. We want to see if ability at performing line-up tests varies with regions of increasingly irregularity. But we again want these definitions of irregular geometry to be plausible. One approach may be to find this distribution of plausible regions empirically: generate c.1000 regions using approach 2, calculate summary statistics on these and sample from different parts of this distribution.

The two summary statistics we explored are Coefficient of Variation in area and Nearest Neighbour Index (NNI).

```r
# function for exploring study regions of varying geography
find.geography <- function(england_msoas, msoas, england_OAs, coefVarMin, coefVarMax, nniMin, nniMax, coefVar)
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
      nni<- mean(nndist(centroids@coords))/(0.5*sqrt(gArea(sample_geoms, byid=FALSE)/length(sample_geoms@data$row)))
    coef_var<- sd(gArea(sample_geoms, byid=TRUE)/1000/1000)/mean(gArea(sample_geoms, byid=TRUE)/1000/1000)

      if(coefVar)
      {
          if(coef_var>coefVarMin && coef_var<coefVarMax && length(sample_geoms) > 44 && length(sample_geoms) < 56 )
          {
            sample_geoms@data$row<-1:length(sample_geoms)
            return(sample_geoms)
            break
          }
      }
      else
      {
        if(nni > nniMin && nni < nniMax && length(sample_geoms) > 44 && length(sample_geoms) < 56 )
          {
            sample_geoms@data$row<-1:length(sample_geoms)
            return(sample_geoms)
            break
          }
      }
    }
  }
}
regular <- find.geography(england_msoas, msoas, england_OAs, 0, 0.5, NULL, NULL, TRUE)
less_regular <- find.geography(england_msoas, msoas, england_OAs, 0.9, 1.0, NULL, NULL, TRUE)
irregular <- find.geography(england_msoas, msoas, england_OAs, 1.3, 1.4, NULL, NULL, TRUE)
more_irregular <- find.geography(england_msoas, msoas, england_OAs, 1.8, 1.9, NULL, NULL, TRUE)
par(mfrow=c(1,4))
plot(regular)
plot(less_regular)
plot(irregular)
plot(more_irregular)
```
![plot of chunk generating_geographies](figures/generating_geographies.png)

## Generating autocorrelated maps

We first define a function for generating synthetic attribute data: rectangular distributions in this case.

```r
create.attribute.data <- function(data)
{
  data@data$value<- NULL
  nrows <- nrow(data@data)
  temp<-  data.frame(matrix(ncol = 2, nrow = nrows))
  temp[,1]<- 1:nrows

  #uniform distribution
  dist<-runif(nrows, min=1, max=10)
  temp[,2]<- dist
  colnames(temp)<- c("index","value")
  temp[,1]<- data@data$CODE
  colnames(temp)<- c("CODE","value")
  data@data<-merge(data@data, temp, by="CODE")
  return(data)
```
Next we create a function for creating maps with a stated Moran's _I_. The simplest means is the permutation based approach used in [Wickham _et al._](http://ieeexplore.ieee.org/xpl/articleDetails.jsp?arnumber=5613434). The problem is that this becomes very slow where wish to have even moderate  Moran's _I_. An alternative option (more of an edit): randomly pick pairs of OAs, swap the attribute values and if difference in _I_ to the target Moran's _I_ decreases, keep the values swapped.

```r
generate.map <- function(data, min, max)
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


  #draw maps
  map<- tm_shape(data) +
    tm_fill(c("permutation"),style="cont", palette="YlOrBr")+
    tm_borders(col="gray80", lwd=1)+
    tm_layout(legend.show=FALSE,frame=FALSE)

  return(map)
}
```

```r
regular<- create.attribute.data(regular)
map1<- generate.map(regular, 0.9,0.91)
map2<- generate.map(regular, 0.7,0.71)
map3<- generate.map(regular, 0.5,0.51)
map4<- generate.map(regular, 0.3,0.31)
map5<- generate.map(regular, 0.1,0.11)
library(grid)
grid.newpage()
pushViewport(viewport(layout=grid.layout(1,5)))
print(map1, vp=viewport(layout.pos.col = 1))
print(map2, vp=viewport(layout.pos.col = 2))
print(map3, vp=viewport(layout.pos.col = 3))
print(map4, vp=viewport(layout.pos.col = 4))
print(map5, vp=viewport(layout.pos.col = 5))
```
![plot of chunk generating_geographies](figures/autocorrelated_maps.png)

## Trying the survey

[under development]

## Analysis

[under development]
