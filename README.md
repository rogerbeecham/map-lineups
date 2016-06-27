
# Supplementary details for _Map LineUps: effects of spatial structure on graphical inference_

_Roger Beecham_ (contact [roger.beecham@city.ac.uk](mailto:roger.beecham@city.ac.uk)) <br>
_Jason Dykes_ <br>
_Wouter Meulemans_ <br>
_Aidan Slingsby_ <br>
_Cagatay Turkay_ <br>
_Jo Wood_ <br>

This document contains supplementary information for our paper, _Map LineUps: effects of spatial structure on graphical inference_.  It outlines the procedure for the experiment (on which the paper is based), code that can be used to run the experiment locally and code used in the data analysis. Note that this draws heavily on work  published in [Harrison _et al._](https://github.com/TuftsVALT/ranking-correlation) and  [Kay & Heer](https://github.com/mjskay/ranking-correlation).


# Experiment

Below is some code and discussion of how we generate the stimuli used in the experiment. The survey software can be found in [experiment/](experiment). Instructions for trying this locally are at the end of this section.  

All stimuli used in the experiment were created using R.  

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

Motivating our study is the need to evaluate Wickham _et al_'s line-up protocol when applied to choropleth maps. We want to come up with recommendations for constructing line-up tests in _real_ data analysis scenarios. So we want ecological validity in the stimuli we create. Because of this, we believe there's a strong argument for using real geographic regions as well as testing against more contrived situations (e.g. regular grids).

Below are two approaches to generating these _real_ regions using English Output Areas (OAs):

1. Load a SpatialDataFrame containing all OAs, sample an OA, then find its 50 nearest neighbours.
2. Load a spatial data frame containing all Middle Super Output Areas MSOAs. MSOAs contain on average 25 OAs. Sample an MSOA and find its nearest neighbour -- thus we end up with c. 50 geographic units, but their grouping is more _real_ since MSOAs are a genuine administrative geography.

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
}
```
Next we develop a function for creating maps with a stated [Moran's _I_](http://link.springer.com/referenceworkentry/10.1007%2F978-0-387-35973-1_817). The simplest means is the permutation based approach used in [Wickham _et al._](http://ieeexplore.ieee.org/xpl/articleDetails.jsp?arnumber=5613434). The problem is that this becomes very slow where we wish to generate even moderate Moran's _I_. An alternative option (more of an edit): randomly pick pairs of OAs, swap the attribute values and if difference in _I_ to the target Moran's _I_ decreases, keep the values swapped.

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

R scripts for generating maps in the directory structure used by our survey software can be found in [src/](src/): ```generate_tests.R```.

## Trying the survey

You will need to install [_MySQL_](https://dev.mysql.com/usingmysql/get_started.html) and  [_MAMP_](https://www.mamp.info/en/) -- the latter of which  enables _PHP_ and _MySQL_ to be run locally. After installing _MAMP_, select ```preferences```, ```web server``` and navigate to ```../experiment/```. After ```start servers```, open a browser and enter ```localhost:8888/admin_initialize.php```. As well as creating the _MySQL_ dbase (called _maplineups_) and required tables, this reads the pushed sample maps stored as .png files in [tests/](experiment/tests/) and creates a ```../stimuli/``` folder, which is used by the dbase and survey software. To reduce the size of this repository regular grids only have been uploaded. Open a _MySQL_ dbase connection via ```root``` and use the recently created  maplineups dbase. This should contain five tables: _lineup_, _lineupanswer_, _map_, _participantgroup_, _user_.  To try the survey enter into a browser: ```localhost:8888```.

Do get in touch if you encounter any problems following these (rather terse) instructions: [roger.beecham@city.ac.uk](mailto:roger.beecham@city.ac.uk).

# Analysis

``` r
# Required packages
library(magrittr)
library(dplyr)
library(ggplot2)
library(RColorBrewer)

# Data
data <- read.csv("data/data.csv", header = TRUE)
```

## Data cleaning

 We are only able to publish aggregate _JND_ scores rather than the raw interactions on which the _JNDs_ are based. These can be compared with those published at [Harrison _et al._'s github](https://github.com/TuftsVALT/ranking-correlation).

 Our data exhibit substantial compression of _JNDs_ due to approach: where the approach is from above and baseline Moran's _I_ is high, scores are overly squashed; and the same happens when the base is low and the approach is from below.

``` r
# Plot raw data and fit linear regression as described in Figure 2 of Kay & Heer -- highlights the problem of compression due to approach.
colours <- brewer.pal(5,"Set1")
names(colours) <- levels(data$approach)
col_scale <- scale_colour_manual(name = "aproach",values = colours)

data  %>%
  ggplot(aes(x=base, y=jnd, color=approach)) +
  geom_point(size=3, alpha=.25) +  
  coord_fixed()+
  stat_smooth(method=lm, se=FALSE, size=1, linetype="dashed") +
  stat_smooth(method=lm, se=FALSE, aes(group=NA), size=1, color="black", linetype="dashed") +
  scale_color_manual(values=c(colours[2], colours[1]))+
  ylim(0,0.8)+
  scale_x_continuous(limits=c(0.1,1), breaks=seq(0,1, by=0.2))+
  facet_wrap(~geography)+
  theme_bw()

```

![plot of chunk raw_jnds_compressed](figures/raw_jnds_compressed.png)

Such a large compression of scores is not observed in  Harrison _et al._'s derived _JNDs_. We also see evidence of substantial between-participant variability (also observed in Harrison _et al._). Notice that variability in _JNDs_ increases as the geography becomes more irregular.

Harrison _et al._ discuss the problem of outliers and use a threshold of 3 absolute deviations from the median for each base _x_ approach _x_ visualization-type combination. This does not solve the substantial outliers we observe for the _irregular real_ geography and a base of 0.7/0.8. Harrison _et al._ also identify a chance boundary for JND – the JND in the staircase procedure that would result from participants randomly guessing through the staircase (JND = 0.45). We also calculate a chance boundary for JND by simulating the staircase procedure, but pay attention to how this boundary varies by each test-case (approach _x_ base pair).

``` r
# Calculate chance by running the simulate_chance.R script
source("src/simulate_chance.R")
data<-merge(data, chance_by_condition)
colnames(chance_by_condition)<- c("base","approach","chance_jnd")
data<-merge(data, chance_by_condition)
data <- data %>% mutate(is_chance = jnd>chance_jnd)
```

Clearly, chance in the staircase will vary for different approach _x_ base pairs and will tend towards the ceilings where the base is high and the approach is from above and the floors where the approach is from below and the base is low. The censoring method described in Kay & Heer may be one approach to treating outliers where scores are not artificially compressed – for example where the base is 0.8, the approach is from below and the estimated JND is 0.7: an obvious outlier. This score would be censored to 0.4, ```min(base−0.05,0.4)```. Given the precision with which we estimate JND, simply censoring to these thresholds would not, as we understand it, remove the observed compression effect. As an example, if the approach is from above and the baseline Moran’s _I_ is 0.7, then Kay & Heer’s censoring would limit _JNDs_ to ```min(0.95 − base, 0.4) → 0.25``` – too small given the _JNDs_ we estimate for 0.7 using the below approach.

The method described in the paper for removing the artificial compression effect due to approach is to remove  approach _x_ base pairs where there is not enough data difference to play with: below with a base of 0.3 and 0.2, and above with a base of 0.7, 0.8 and 0.9:

```r
# Remove ceiling and floors due to approach
data <- data %>%
  mutate(exclude = ifelse( (base < 0.4  & approach == "below")
                           | (base> 0.6 & approach == "above"),
                         TRUE, FALSE))

# And resample midbases to prevent giving greater weight to these test cases in the analysis.
source("src/resample_midbases.R")

```  

Finally, we decide on how to clean outliers. Given that our test is comparatively more challenging than the non-spatial equivalent – it is conceivable that, for the irregular geography, participants could not distinguish between a Moran’s _I_ of 0.4 and 0.8 – we decide against pinning outliers to the chance threshold used in Kay & Heer (of ~0.4). Instead we remove all estimated _JNDs_ where the accuracy rate on which the score is based begins to approach chance (< 0.55).

## Model specification

We first compare differences in mean _JND_ observed for each geography and find that as the geometry of our study regions becomes more irregular, _JND_ increases.  

``` r
library(effsize)

cohens_data <- data_model %>% filter(accuracy>0.55)

cohen.d(cohens_data[cohens_data$geography=="1_grid",]$jnd, cohens_data[cohens_data$geography=="3_irreg" ,]$jnd)

## d estimate: -0.7060453 (medium)
## 95 percent confidence interval:
##        inf        sup
## -0.9107018 -0.5013888

cohen.d(cohens_data[cohens_data$geography=="1_grid",]$jnd, cohens_data[cohens_data$geography=="2_reg" ,]$jnd)

## d estimate: -0.3071126 (small)
## 95 percent confidence interval:
##        inf        sup
## -0.5037327 -0.1104925

cohen.d(cohens_data[cohens_data$geography=="2_reg",]$jnd, cohens_data[cohens_data$geography=="3_irreg" ,]$jnd)

# Cohen's d

## d estimate: -0.4094879 (small)
## 95 percent confidence interval:
##       inf        sup
## -0.5964104 -0.2225653

```

Following Kay & Heer, we compare linear models with and without log transformation of the outcome (_JND_). We borrow the plotting function provided at [Kay & Heer's github](https://github.com/mjskay/ranking-correlation). Log transformation improves problems of skew and kurtosis in residuals for the _regular real_ geography.

``` r
library(gamlss)
m.linear.reg = gamlss(jnd ~ base,data=data_model%>% filter(geography=="2_reg", accuracy>0.55)

m.loglinear.reg = gamlss(jnd ~ base, data=data_model %>% filter(geography=="2_reg", accuracy>0.55), family=LOGNO)

# This uses the plotting function provided by Kay & Heer.
plot_model_residuals(m.linear.reg)
plot_model_residuals(m.loglinear.reg)
```

![plot of chunk residuals_linear_vs_log](figures/residuals_linear_vs_log.png)

Kay & Heer also identify the problem of participant effects. Each participant contributes up to four data points and we might expect two randomly sampled observations from the same participant to be more similar than from a different participant. We therefore add a varying-intercept random effect on participant. We define one model for each geography.

```
library(lme4)
m.grid <- lmer(log(jnd) ~ base + (1|user_id), data=data_model %>% filter(geography=="1_grid", accuracy>0.55))
m.reg <- lmer(log(jnd) ~ base + (1|user_id), data=data_model %>% filter(geography=="2_reg", accuracy>0.55))
m.irreg <- lmer(log(jnd) ~ base + (1|user_id), data=data_model %>% filter(geography=="3_irreg", accuracy>0.55))
library(MuMIn) # for pseudo-rsquared
r.squaredGLMM(m.reg)

```

We evaluate the models using estimated regression coefficients, model fits and prediction intervals. Note that direct comparisons between _regular grid_ and the _real_ geographies should be treated cautiously since we do not have data at bases of 0.2 and 0.9 for the _regular grid_ case.

|      complexity      | exp(intercept) |   exp(slope)  | pseudo R<sup>2</sup> fixed | pseudo R<sup>2</sup> fixed+random |
|:--------------------:|:--------------:|:-------------:|:--------------------------:|:---------------------------------:|
|    _regular grid_    |      0.311     |    -0.344     |           0.15             |              0.16                 |
|    _regular real_    |      0.312     |    -0.438     |           0.12             |              0.42                 |
|   _irregular real_   |      0.424     |    -0.370     |           0.13             |              0.51                 |

```r
source("src/plot_prediction_intervals.R")
```

![plot of chunk plot_prediction_intervals](figures/plot_prediction_intervals.png)
