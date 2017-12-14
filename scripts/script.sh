#!/bin/bash
#echo "Start"
error=0
#rename
input_path=/var/lib/misc/records/leg
output_path=/var/lib/misc/records

cd $input_path

#parse data
j=0
t=0
for p in $*
do
k=$(($j*3+2))
kk=$(($j*3+3))
kkk=$(($j*3+4))
if [ $k -eq $t ]
then
part[$j]=$p
elif [ $kk -eq $t ]
then
node[$j]=$p
elif [ $kkk -eq $t ]
then
duration[$j]=$p
j=$(($j+1))
fi
t=$(($t+1))
done

#rename
for ((i=0; i<$2; i++))
do
file_name=$1"_p"${part[$i]}"_"
for ((k=0; k<${node[$i]}; k++))
do
name=$file_name$k.slin
new_name=$file_name$k.s16
if [ -e $name ]
then
cp $name $new_name
else
error=1
echo "Error no find file $name"
break
fi
done
done

if [ $error -eq 1 ]
then
file_delet=$1"*.s16"
rm -f $file_delet
exit 1
fi

cmd="sox "
for ((i=0; i<$2; i++))
do
cmd=$cmd"-t sox \""
for ((k=0; k<${node[$i]}; k++))
do
if [ $k -eq 0 ]
then
cmd=$cmd"|sox -r 8k -c 1 -e signed-integer $1"_p"${part[$i]}"_"$k.s16 -p pad 0 2 "
else
cmd=$cmd"|sox - -m -r 8k -c 1 -e signed-integer $1"_p"${part[$i]}"_"$k.s16 -p "
fi
done
cmd=$cmd"|sox - -p trim 0 ${duration[$i]}\" " 
done
#cmd=$cmd"$1.wav norm vad "
cmd=$cmd"$1.wav"

#echo "$cmd"
eval $cmd

wav_file=$1.wav
if [ -e  $wav_file ]
then
error=0
else
error=2
file_delet=$1"*.s16"
rm -f $file_delet
echo "Error sox command"
exit 2
fi

#sleep 20s

mp3_file=$output_path"/"$1".mp3"
convert_cmd="lame --silent --preset phone "$wav_file" "$mp3_file
eval $convert_cmd

if [ -e  $mp3_file ]
then
error=0
file_delet=$1"*"
rm -f $file_delet
else
error=3
file_delet=$1"*.s16"
rm -f $file_delet
file_delet=$1"*.wav"
rm -f $file_delet
echo "Error lame command"
exit 3
fi

chown www-data $mp3_file
exit 0
#echo "Done "